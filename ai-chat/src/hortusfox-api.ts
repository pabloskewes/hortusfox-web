import { config } from "./config.js";

export type QueryValue = string | number | boolean | undefined | null;

function pathWithToken(path: string): URL {
    const cleanedPath = path.replace(/^\/+/, "");
    const url = new URL(`${config.apiBaseUrl}/api/${cleanedPath}`);
    url.searchParams.set("token", config.apiToken);
    return url;
}

function appendQuery(url: URL, query: Record<string, QueryValue>): void {
    for (const [key, value] of Object.entries(query)) {
        if (value === undefined || value === null) continue;
        url.searchParams.set(key, String(value));
    }
}

function buildFormBody(body: Record<string, QueryValue>): string {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(body)) {
        if (value === undefined || value === null) continue;
        params.set(key, String(value));
    }
    return params.toString();
}

export async function apiGet<T = unknown>(path: string, query: Record<string, QueryValue> = {}): Promise<T> {
    const url = pathWithToken(path);
    appendQuery(url, query);
    return apiFetch<T>(url, { method: "GET" });
}

export async function apiPost<T = unknown>(path: string, body: Record<string, QueryValue> = {}): Promise<T> {
    const url = pathWithToken(path);
    return apiFetch<T>(url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: buildFormBody(body),
    });
}

async function apiFetch<T>(url: URL, init: RequestInit): Promise<T> {
    let res: Response;
    try {
        res = await fetch(url, init);
    } catch (cause) {
        const reason = cause instanceof Error ? cause.message : String(cause);
        throw new Error(`HortusFox API request failed: ${reason}`);
    }

    let body: unknown;
    try {
        body = await res.json();
    } catch {
        throw new Error(`HortusFox API returned non-JSON body (HTTP ${res.status})`);
    }

    if (!res.ok) {
        throw new Error(`HortusFox API returned HTTP ${res.status}: ${JSON.stringify(body)}`);
    }

    if (
        body !== null &&
        typeof body === "object" &&
        "code" in body &&
        typeof (body as { code: unknown }).code === "number" &&
        (body as { code: number }).code !== 200
    ) {
        throw new Error(`HortusFox API returned application code ${(body as { code: number }).code}: ${JSON.stringify(body)}`);
    }

    return cleanApiResponse(body as T);
}

function cleanApiResponse<T>(value: T): T {
    if (Array.isArray(value)) {
        return value.map((item) => cleanApiResponse(item)) as T;
    }

    if (value && typeof value === "object") {
        const result: Record<string, unknown> = {};
        for (const [key, item] of Object.entries(value)) {
            if (/^\d+$/.test(key)) continue;
            result[key] = cleanApiResponse(item);
        }
        return result as T;
    }

    return value;
}
