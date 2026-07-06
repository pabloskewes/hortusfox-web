export class HortusFoxApiError extends Error {
    public readonly status: number;
    public readonly body: unknown;

    constructor(status: number, body: unknown, message: string) {
        super(message);
        this.name = "HortusFoxApiError";
        this.status = status;
        this.body = body;
    }
}

export class HortusFoxHttpError extends Error {
    public override readonly cause: unknown;

    constructor(cause: unknown, message: string) {
        super(message, { cause });
        this.name = "HortusFoxHttpError";
    }
}

function isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === "object" && value !== null && !Array.isArray(value);
}

export function formatErrorForLlm(err: unknown): string {
    if (err instanceof HortusFoxHttpError) {
        const detail = err.cause instanceof Error ? err.cause.message : String(err.cause);
        return (
            `Could not reach HortusFox API: ${err.message} (${detail}). ` +
            `Check HORTUSFOX_API_BASE_URL and that the HortusFox server is running.`
        );
    }

    if (err instanceof HortusFoxApiError) {
        if (isObject(err.body) && "invalid_token" in err.body) {
            return (
                `HortusFox API rejected the token (HTTP ${err.status}). ` +
                `Check HORTUSFOX_API_TOKEN in mcp/.env.`
            );
        }
        if (isObject(err.body) && typeof err.body.msg === "string") {
            return `HortusFox API error: ${err.body.msg}`;
        }
        if (isObject(err.body) && typeof err.body.code === "number" && err.body.code !== 200) {
            return `HortusFox API returned application code ${err.body.code}.`;
        }
        return `HortusFox API error (HTTP ${err.status}): ${JSON.stringify(err.body)}`;
    }

    if (err instanceof Error) {
        return `Unexpected error: ${err.message}`;
    }

    return `Unexpected error: ${String(err)}`;
}
