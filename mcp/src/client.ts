import { config } from "./config.js";
import { HortusFoxApiError, HortusFoxHttpError } from "./errors.js";

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

export async function apiGet<T = unknown>(
    path: string,
    query: Record<string, QueryValue> = {}
): Promise<T> {
    const url = pathWithToken(path);
    appendQuery(url, query);
    return apiFetch<T>(url, { method: "GET" });
}

export async function apiPost<T = unknown>(
    path: string,
    body: Record<string, QueryValue> = {}
): Promise<T> {
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
        throw new HortusFoxHttpError(cause, `request to ${url.toString()} failed: ${reason}`);
    }

    let body: unknown;
    try {
        body = await res.json();
    } catch {
        throw new HortusFoxApiError(
            res.status,
            null,
            `API returned non-JSON body (HTTP ${res.status})`
        );
    }

    if (!res.ok) {
        throw new HortusFoxApiError(res.status, body, `API returned HTTP ${res.status}`);
    }

    if (
        body !== null &&
        typeof body === "object" &&
        "code" in body &&
        typeof (body as { code: unknown }).code === "number" &&
        (body as { code: number }).code !== 200
    ) {
        throw new HortusFoxApiError(
            res.status,
            body,
            `API returned application code ${(body as { code: number }).code}`
        );
    }

    return body as T;
}
