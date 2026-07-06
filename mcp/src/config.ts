import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";
import { config as loadDotenv } from "dotenv";

const moduleFilename = fileURLToPath(import.meta.url);
const moduleDir = dirname(moduleFilename);
const mcpRoot = resolve(moduleDir, "..");

loadDotenv({ path: resolve(mcpRoot, ".env") });

function required(name: string): string {
    const value = process.env[name];
    if (!value) {
        throw new Error(
            `Missing required env var: ${name}. ` +
            `Copy mcp/.env.example to mcp/.env and fill it in.`
        );
    }
    return value;
}

function optionalInt(name: string, defaultValue: number): number {
    const raw = process.env[name];
    if (raw === undefined || raw === "") {
        return defaultValue;
    }
    const parsed = parseInt(raw, 10);
    if (Number.isNaN(parsed)) {
        throw new Error(`Env var ${name} must be an integer, got: ${raw}`);
    }
    return parsed;
}

const rawBaseUrl = required("HORTUSFOX_API_BASE_URL").replace(/\/+$/, "");

export const config = {
    apiBaseUrl: rawBaseUrl,
    apiToken: required("HORTUSFOX_API_TOKEN"),
    defaultUserId: optionalInt("HORTUSFOX_DEFAULT_USER_ID", 1),
} as const;
