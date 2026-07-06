const NUMERIC_KEY = /^\d+$/;

export function cleanApiResponse<T>(value: T): T {
    return clean(value) as T;
}

function clean(value: unknown): unknown {
    if (Array.isArray(value)) {
        return value.map(clean);
    }
    if (value !== null && typeof value === "object") {
        const out: Record<string, unknown> = {};
        for (const [key, val] of Object.entries(value as Record<string, unknown>)) {
            if (NUMERIC_KEY.test(key)) continue;
            out[key] = clean(val);
        }
        return out;
    }
    return value;
}
