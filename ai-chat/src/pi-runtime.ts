import {
    AuthStorage,
    DefaultResourceLoader,
    ModelRegistry,
    SessionManager,
    SettingsManager,
    createAgentSession,
} from "@earendil-works/pi-coding-agent";
import { resolve } from "node:path";
import { config, sidecarRoot } from "./config.js";
import { createHortusFoxTools } from "./tools.js";
import type { ChatRequest, ChatResponse, PendingAction } from "./types.js";

export async function runChatTurn(request: ChatRequest): Promise<ChatResponse> {
    const pendingActions: PendingAction[] = [];
    const modelPattern = request.model || config.model;
    const thinkingLevel = request.thinking_level || config.thinkingLevel;

    const authStorage = AuthStorage.create(resolve(config.agentDir, "auth.json"));
    const modelRegistry = ModelRegistry.create(authStorage, resolve(config.agentDir, "models.json"));
    const model = resolveModel(modelRegistry, modelPattern);
    if (!model) {
        throw new Error(`Could not resolve pi model: ${modelPattern}`);
    }

    const customTools = createHortusFoxTools({
        allowedTools: request.allowed_tools,
        mutationTools: request.mutation_tools,
        collectPendingAction: (action) => pendingActions.push(action),
    });

    const settingsManager = SettingsManager.inMemory({
        defaultModel: model.id,
        defaultProvider: String(model.provider),
        defaultThinkingLevel: thinkingLevel as "off" | "minimal" | "low" | "medium" | "high" | "xhigh",
        packages: [],
        extensions: [],
        skills: [],
        prompts: [],
        themes: [],
        enableSkillCommands: false,
        hideThinkingBlock: true,
    });

    const resourceLoader = new DefaultResourceLoader({
        cwd: sidecarRoot,
        agentDir: config.agentDir,
        settingsManager,
        noExtensions: true,
        noSkills: true,
        noPromptTemplates: true,
        noThemes: true,
        noContextFiles: true,
        systemPrompt: buildSystemPrompt(),
    });
    await resourceLoader.reload();

    const { session } = await createAgentSession({
        cwd: sidecarRoot,
        agentDir: config.agentDir,
        authStorage,
        modelRegistry,
        model,
        thinkingLevel: thinkingLevel as "off" | "minimal" | "low" | "medium" | "high" | "xhigh",
        resourceLoader,
        settingsManager,
        sessionManager: SessionManager.inMemory(sidecarRoot),
        customTools,
        tools: customTools.map((tool) => tool.name),
    });

    try {
        await session.prompt(buildTurnPrompt(request));
        return {
            reply: extractLastAssistantText(session.messages),
            pending_actions: pendingActions,
            model: `${String(model.provider)}/${model.id}`,
        };
    } finally {
        session.dispose();
    }
}

function resolveModel(registry: ModelRegistry, pattern: string) {
    const trimmed = pattern.trim();
    if (trimmed.includes("/")) {
        const [provider, ...rest] = trimmed.split("/");
        const id = rest.join("/");
        if (provider && id) {
            const exact = registry.find(provider, id);
            if (exact) return exact;
        }
    }

    const normalized = normalizeModelPattern(trimmed);
    return registry.getAvailable().find((model) => {
        const id = normalizeModelPattern(model.id);
        const name = normalizeModelPattern(model.name ?? "");
        const provider = normalizeModelPattern(String(model.provider));
        return id.includes(normalized) || name.includes(normalized) || `${provider}/${id}`.includes(normalized);
    }) ?? registry.getAll().find((model) => {
        const id = normalizeModelPattern(model.id);
        const name = normalizeModelPattern(model.name ?? "");
        const provider = normalizeModelPattern(String(model.provider));
        return id.includes(normalized) || name.includes(normalized) || `${provider}/${id}`.includes(normalized);
    });
}

function normalizeModelPattern(value: string): string {
    return value.toLowerCase().replace(/[^a-z0-9]+/g, "");
}

function buildSystemPrompt(): string {
    return `You are HortusFox's in-app plant assistant.

Use the provided HortusFox tools as the source of truth for workspace data. For plant care questions about watering, light, humidity, temperature, soil, or fertilization, use get_species_info whenever a scientific name is available or can be found with get_plant/list_plants/search_plants. Do not answer care questions from general knowledge when the tool can answer.

Mutation tools do not execute immediately. When you call add_plant, update_plant, add_task, or add_moisture_reading, the app creates a pending action that the user must approve in the UI. Describe the pending change clearly and do not claim it has already been done.

Be concise, practical, and honest about missing data.`;
}

function buildTurnPrompt(request: ChatRequest): string {
    const history = (request.messages ?? [])
        .slice(-20)
        .map((message) => `${message.role === "user" ? "User" : "Assistant"}: ${message.content}`)
        .join("\n\n");

    return `${history ? `Conversation so far:\n${history}\n\n` : ""}Current user message:\n${request.user_message}`;
}

function extractLastAssistantText(messages: Array<{ role?: string; content?: unknown }>): string {
    for (let i = messages.length - 1; i >= 0; i--) {
        const message = messages[i];
        if (!message || message.role !== "assistant") continue;
        return extractText(message.content).trim();
    }
    return "";
}

function extractText(content: unknown): string {
    if (typeof content === "string") return content;
    if (Array.isArray(content)) {
        return content.map((item) => {
            if (typeof item === "string") return item;
            if (item && typeof item === "object" && "text" in item) {
                return String((item as { text: unknown }).text ?? "");
            }
            return "";
        }).join("\n");
    }
    return "";
}
