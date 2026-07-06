import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { homedir } from "node:os";
import { config as loadDotenv } from "dotenv";

const moduleFilename = fileURLToPath(import.meta.url);
const moduleDir = dirname(moduleFilename);
export const sidecarRoot = resolve(moduleDir, "..");
export const projectRoot = resolve(sidecarRoot, "..");

loadDotenv({ path: resolve(projectRoot, "mcp", ".env") });
loadDotenv({ path: resolve(sidecarRoot, ".env"), override: true });

function required(name: string): string {
    const value = process.env[name];
    if (!value) {
        throw new Error(`Missing required env var: ${name}`);
    }
    return value;
}

function optionalInt(name: string, fallback: number): number {
    const raw = process.env[name];
    if (!raw) return fallback;
    const parsed = parseInt(raw, 10);
    if (Number.isNaN(parsed)) {
        throw new Error(`Env var ${name} must be an integer, got: ${raw}`);
    }
    return parsed;
}

function optionalCsv(name: string): string[] | undefined {
    const raw = process.env[name];
    if (!raw) return undefined;
    return raw.split(",").map((item) => item.trim()).filter(Boolean);
}

export const config = {
    host: process.env.AI_CHAT_HOST ?? "127.0.0.1",
    port: optionalInt("AI_CHAT_PORT", 8787),
    sidecarToken: required("AI_CHAT_SIDECAR_TOKEN"),
    model: process.env.AI_CHAT_MODEL ?? "opencode-go/deepseek-v4-pro",
    thinkingLevel: process.env.AI_CHAT_THINKING_LEVEL ?? "off",
    agentDir: process.env.PI_CODING_AGENT_DIR ?? resolve(homedir(), ".pi", "agent"),
    apiBaseUrl: required("HORTUSFOX_API_BASE_URL").replace(/\/+$/, ""),
    apiToken: required("HORTUSFOX_API_TOKEN"),
    defaultUserId: optionalInt("HORTUSFOX_DEFAULT_USER_ID", 1),
    allowedTools: optionalCsv("AI_CHAT_ALLOWED_TOOLS"),
    mutationTools: optionalCsv("AI_CHAT_MUTATION_TOOLS"),
} as const;
