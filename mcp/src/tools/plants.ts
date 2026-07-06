import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { apiGet, apiPost } from "../client.js";
import { jsonResult, errorResult } from "./_util.js";

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

export function registerPlantTools(server: McpServer): void {
    server.tool(
        "list_plants",
        "List plants in the HortusFox workspace, optionally filtered by location.",
        {
            location_id: z.number().int().positive()
                .optional()
                .describe("Restrict the list to plants in this location ID."),
            limit: z.number().int().positive()
                .optional()
                .describe("Maximum number of plants to return."),
        },
        async ({ location_id, limit }) => {
            try {
                const result = await apiGet("plants/list", {
                    location: location_id,
                    limit,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );

    server.tool(
        "get_plant",
        "Get a single plant by ID, including all default and custom attributes.",
        {
            plant_id: z.number().int().positive()
                .describe("The plant ID to fetch."),
        },
        async ({ plant_id }) => {
            try {
                const result = await apiGet("plants/get", { plant: plant_id });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );

    server.tool(
        "add_plant",
        "Add a new plant to a location. The new plant starts with default values; use update_plant to set additional attributes afterwards.",
        {
            name: z.string().min(1)
                .describe("Display name for the plant."),
            location_id: z.number().int().positive()
                .describe("ID of the location (room) the plant belongs to."),
        },
        async ({ name, location_id }) => {
            try {
                const result = await apiPost("plants/add", {
                    name,
                    location: location_id,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );

    server.tool(
        "update_plant",
        `Update a single attribute of a plant. The API updates one attribute at a time, so to change multiple fields, call this tool multiple times.

Allowed attributes: ${ALLOWED_ATTRIBUTES.join(", ")}.

For setting a value to NULL, pass the string "#null" (the API's convention for null).`,
        {
            plant_id: z.number().int().positive()
                .describe("The plant ID to update."),
            attribute: z.enum(ALLOWED_ATTRIBUTES)
                .describe("The attribute name to update."),
            value: z.string()
                .describe("The new value as a string. Use \"#null\" to set the attribute to NULL. Numeric values (e.g. humidity) should be passed as their string representation."),
        },
        async ({ plant_id, attribute, value }) => {
            try {
                const result = await apiPost("plants/update", {
                    plant: plant_id,
                    attribute,
                    value,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );

    server.tool(
        "search_plants",
        "Full-text search across plant names, scientific names, notes, and tags.",
        {
            query: z.string().min(1)
                .describe("The search expression."),
            limit: z.number().int().positive()
                .optional()
                .describe("Maximum number of results to return."),
        },
        async ({ query, limit }) => {
            try {
                const result = await apiGet("plants/search", {
                    expression: query,
                    limit,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );
}
