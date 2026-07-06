import type { AgentToolResult, ToolDefinition } from "@earendil-works/pi-coding-agent";
import { Type } from "typebox";
import { apiGet, apiPost } from "./hortusfox-api.js";
import { config } from "./config.js";
import type { PendingAction } from "./types.js";

const RECURRING_SCOPES = ["hours", "days", "weeks", "months", "years"] as const;
const ALLOWED_ATTRIBUTES = [
    "name",
    "scientific_name",
    "knowledge_link",
    "location",
    "tags",
    "photo",
    "last_watered",
    "last_repotted",
    "last_fertilised",
    "lifespan",
    "hardy",
    "cutting_month",
    "date_of_purchase",
    "humidity",
    "light_level",
    "health_state",
    "notes",
    "history",
    "history_date",
] as const;

export const DEFAULT_ALLOWED_TOOLS = [
    "list_locations",
    "list_plants",
    "get_plant",
    "search_plants",
    "list_tasks",
    "list_moisture_readings",
    "get_species_info",
    "add_plant",
    "update_plant",
    "add_task",
    "add_moisture_reading",
];

export const DEFAULT_MUTATION_TOOLS = [
    "add_plant",
    "update_plant",
    "add_task",
    "add_moisture_reading",
];

type ToolArgs = Record<string, unknown>;
type PendingCollector = (action: PendingAction) => void;

export function createHortusFoxTools(options: {
    allowedTools?: string[];
    mutationTools?: string[];
    collectPendingAction: PendingCollector;
}): ToolDefinition[] {
    const allowed = new Set(options.allowedTools ?? config.allowedTools ?? DEFAULT_ALLOWED_TOOLS);
    const mutations = new Set(options.mutationTools ?? config.mutationTools ?? DEFAULT_MUTATION_TOOLS);

    const definitions = getAllToolDefinitions(mutations, options.collectPendingAction);
    return definitions.filter((tool) => allowed.has(tool.name));
}

export async function executeApprovedAction(toolName: string, args: ToolArgs): Promise<unknown> {
    if (!DEFAULT_MUTATION_TOOLS.includes(toolName)) {
        throw new Error(`Tool is not approved for action execution: ${toolName}`);
    }

    return executeTool(toolName, args, true);
}

function getAllToolDefinitions(mutationTools: Set<string>, collectPendingAction: PendingCollector): ToolDefinition[] {
    return [
        {
            name: "list_locations",
            label: "List locations",
            description: "List all locations (rooms) in the HortusFox workspace. Optionally include plants in each location.",
            parameters: Type.Object({
                include_plants: Type.Optional(Type.Boolean()),
            }),
            execute: async (_id, params) => toolResult(await executeTool("list_locations", params as ToolArgs)),
        },
        {
            name: "list_plants",
            label: "List plants",
            description: "List plants in the HortusFox workspace, optionally filtered by location.",
            parameters: Type.Object({
                location_id: Type.Optional(Type.Integer({ minimum: 1 })),
                limit: Type.Optional(Type.Integer({ minimum: 1 })),
            }),
            execute: async (_id, params) => toolResult(await executeTool("list_plants", params as ToolArgs)),
        },
        {
            name: "get_plant",
            label: "Get plant",
            description: "Get a single plant by ID, including default and custom attributes.",
            parameters: Type.Object({
                plant_id: Type.Integer({ minimum: 1 }),
            }),
            execute: async (_id, params) => toolResult(await executeTool("get_plant", params as ToolArgs)),
        },
        {
            name: "search_plants",
            label: "Search plants",
            description: "Full-text search across plant names, scientific names, notes, and tags.",
            parameters: Type.Object({
                query: Type.String({ minLength: 1 }),
                limit: Type.Optional(Type.Integer({ minimum: 1 })),
            }),
            execute: async (_id, params) => toolResult(await executeTool("search_plants", params as ToolArgs)),
        },
        {
            name: "list_tasks",
            label: "List tasks",
            description: "List tasks in the HortusFox workspace, optionally filtered by completion state.",
            parameters: Type.Object({
                done: Type.Optional(Type.Boolean()),
                limit: Type.Optional(Type.Integer({ minimum: 1 })),
            }),
            execute: async (_id, params) => toolResult(await executeTool("list_tasks", params as ToolArgs)),
        },
        {
            name: "list_moisture_readings",
            label: "List moisture readings",
            description: "List moisture meter readings (1-9 scale) for a plant, ordered most recent first.",
            parameters: Type.Object({
                plant_id: Type.Integer({ minimum: 1 }),
                limit: Type.Optional(Type.Integer({ minimum: 1 })),
            }),
            execute: async (_id, params) => toolResult(await executeTool("list_moisture_readings", params as ToolArgs)),
        },
        {
            name: "get_species_info",
            label: "Get species care info",
            description: "Look up cached care data for a plant species: watering, light, humidity, soil, and temperature ranges. Use this for care questions instead of general knowledge.",
            parameters: Type.Object({
                scientific_name: Type.String({ minLength: 1 }),
            }),
            execute: async (_id, params) => toolResult(await executeTool("get_species_info", params as ToolArgs)),
        },
        mutationTool("add_plant", "Add plant", "Prepare adding a new plant to a location. Requires user approval before execution.", Type.Object({
            name: Type.String({ minLength: 1 }),
            location_id: Type.Integer({ minimum: 1 }),
        }), mutationTools, collectPendingAction),
        mutationTool("update_plant", "Update plant", "Prepare updating one plant attribute. Requires user approval before execution.", Type.Object({
            plant_id: Type.Integer({ minimum: 1 }),
            attribute: Type.Union(ALLOWED_ATTRIBUTES.map((value) => Type.Literal(value))),
            value: Type.String(),
        }), mutationTools, collectPendingAction),
        mutationTool("add_task", "Add task", "Prepare creating a task. Requires user approval before execution.", Type.Object({
            title: Type.String({ minLength: 1 }),
            description: Type.Optional(Type.String()),
            due_date: Type.Optional(Type.String()),
            recurring_time: Type.Optional(Type.Integer({ minimum: 1 })),
            recurring_scope: Type.Optional(Type.Union(RECURRING_SCOPES.map((value) => Type.Literal(value)))),
            plant_id: Type.Optional(Type.Integer({ minimum: 1 })),
        }), mutationTools, collectPendingAction),
        mutationTool("add_moisture_reading", "Add moisture reading", "Prepare adding a moisture meter reading. Requires user approval before execution.", Type.Object({
            plant_id: Type.Integer({ minimum: 1 }),
            value: Type.Integer({ minimum: 1, maximum: 9 }),
            note: Type.Optional(Type.String()),
            taken_at: Type.Optional(Type.String()),
            user_id: Type.Optional(Type.Integer({ minimum: 1 })),
        }), mutationTools, collectPendingAction),
    ];
}

function mutationTool(
    name: string,
    label: string,
    description: string,
    parameters: ToolDefinition["parameters"],
    mutationTools: Set<string>,
    collectPendingAction: PendingCollector,
): ToolDefinition {
    return {
        name,
        label,
        description,
        parameters,
        execute: async (_id, params) => {
            if (!mutationTools.has(name)) {
                throw new Error(`Mutation tool disabled: ${name}`);
            }
            const action = buildPendingAction(name, params as ToolArgs);
            collectPendingAction(action);
            return toolResult({
                status: "confirmation_required",
                client_action_id: action.client_action_id,
                message: `Prepared ${name}. The HortusFox UI must show the preview and the user must approve before it executes.`,
                preview: action.preview,
            });
        },
    };
}

async function executeTool(toolName: string, args: ToolArgs, approvedMutation = false): Promise<unknown> {
    switch (toolName) {
        case "list_locations":
            return apiGet("locations/list", { include_plants: boolArg(args.include_plants, false) });
        case "list_plants":
            return apiGet("plants/list", { location: intArg(args.location_id), limit: intArg(args.limit) });
        case "get_plant":
            return apiGet("plants/get", { plant: intArg(args.plant_id, true) });
        case "search_plants":
            return apiGet("plants/search", { expression: stringArg(args.query, true), limit: intArg(args.limit) });
        case "list_tasks":
            return apiGet("tasks/fetch", { done: boolArg(args.done, false), limit: intArg(args.limit) ?? 100 });
        case "list_moisture_readings":
            return apiGet("moisture/fetch", { plant: intArg(args.plant_id, true), limit: intArg(args.limit) });
        case "get_species_info":
            return apiGet("species/info", { scientific_name: stringArg(args.scientific_name, true) });
        case "add_plant":
            assertApproved(toolName, approvedMutation);
            return apiPost("plants/add", { name: stringArg(args.name, true), location: intArg(args.location_id, true) });
        case "update_plant":
            assertApproved(toolName, approvedMutation);
            return apiPost("plants/update", {
                plant: intArg(args.plant_id, true),
                attribute: stringArg(args.attribute, true),
                value: stringArg(args.value, false),
            });
        case "add_task":
            assertApproved(toolName, approvedMutation);
            return apiPost("tasks/add", {
                title: stringArg(args.title, true),
                description: stringArg(args.description, false),
                due_date: optionalStringArg(args.due_date),
                recurring_time: intArg(args.recurring_time) ?? 0,
                recurring_scope: stringArg(args.recurring_scope, false) || "hours",
                plant: intArg(args.plant_id) ?? 0,
            });
        case "add_moisture_reading":
            assertApproved(toolName, approvedMutation);
            return apiPost("moisture/add", {
                plant: intArg(args.plant_id, true),
                value: intArg(args.value, true),
                note: stringArg(args.note, false),
                taken_at: optionalStringArg(args.taken_at),
                user: intArg(args.user_id) ?? config.defaultUserId,
            });
        default:
            throw new Error(`Unknown tool: ${toolName}`);
    }
}

function toolResult(result: unknown): AgentToolResult<Record<string, unknown>> {
    return {
        content: [{ type: "text", text: JSON.stringify(result, null, 2) }],
        details: typeof result === "object" && result !== null ? result as Record<string, unknown> : { result },
    };
}

function buildPendingAction(toolName: string, args: ToolArgs): PendingAction {
    const fields = Object.entries(args).map(([label, value]) => ({ label, value: stringifyValue(value) }));
    const summaryByTool: Record<string, string> = {
        add_plant: `Add plant "${stringifyValue(args.name)}" to location ${stringifyValue(args.location_id)}`,
        update_plant: `Update plant ${stringifyValue(args.plant_id)}: ${stringifyValue(args.attribute)} = ${stringifyValue(args.value)}`,
        add_task: `Create task "${stringifyValue(args.title)}"`,
        add_moisture_reading: `Add moisture reading ${stringifyValue(args.value)}/9 for plant ${stringifyValue(args.plant_id)}`,
    };

    return {
        client_action_id: `${toolName}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
        tool_name: toolName,
        args,
        preview: {
            title: toolName.replace(/_/g, " "),
            summary: summaryByTool[toolName] ?? `Run ${toolName}`,
            fields,
        },
    };
}

function assertApproved(toolName: string, approved: boolean): void {
    if (!approved) {
        throw new Error(`Refusing to execute mutation without approval: ${toolName}`);
    }
}

function stringArg(value: unknown, required: boolean): string {
    if (value === undefined || value === null) {
        if (required) throw new Error("Required string argument missing");
        return "";
    }
    return String(value);
}

function optionalStringArg(value: unknown): string | undefined {
    if (value === undefined || value === null || value === "") {
        return undefined;
    }
    return String(value);
}

function intArg(value: unknown, required = false): number | undefined {
    if (value === undefined || value === null || value === "") {
        if (required) throw new Error("Required integer argument missing");
        return undefined;
    }
    const parsed = typeof value === "number" ? value : parseInt(String(value), 10);
    if (!Number.isInteger(parsed)) {
        throw new Error(`Expected integer argument, got: ${String(value)}`);
    }
    return parsed;
}

function boolArg(value: unknown, fallback: boolean): boolean {
    if (value === undefined || value === null || value === "") return fallback;
    if (typeof value === "boolean") return value;
    return String(value) === "true" || String(value) === "1";
}

function stringifyValue(value: unknown): string {
    if (value === undefined || value === null || value === "") return "(empty)";
    if (typeof value === "string") return value;
    return JSON.stringify(value);
}
