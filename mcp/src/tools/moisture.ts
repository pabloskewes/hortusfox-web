import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { apiGet, apiPost } from "../client.js";
import { config } from "../config.js";
import { jsonResult, errorResult } from "./_util.js";

export function registerMoistureTools(server: McpServer): void {
    server.tool(
        "list_moisture_readings",
        "List moisture meter readings (1-9 scale) for a plant, ordered most recent first.",
        {
            plant_id: z.number().int().positive()
                .describe("The plant ID to fetch readings for."),
            limit: z.number().int().positive()
                .optional()
                .describe("Maximum number of readings to return."),
        },
        async ({ plant_id, limit }) => {
            try {
                const result = await apiGet("moisture/fetch", {
                    plant: plant_id,
                    limit,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );

    server.tool(
        "add_moisture_reading",
        `Add a new moisture meter reading (1-9 scale) for a plant. If user_id is omitted, the default user from mcp/.env (HORTUSFOX_DEFAULT_USER_ID, default 1) is used.

taken_at format: "YYYY-MM-DD HH:MM:SS". If omitted, the server's current time is used.`,
        {
            plant_id: z.number().int().positive()
                .describe("The plant ID this reading is for."),
            value: z.number().int().min(1).max(9)
                .describe("Moisture reading on the 1-9 scale (1 = dry, 9 = wet)."),
            note: z.string()
                .optional()
                .describe("Optional free-text note for this reading."),
            taken_at: z.string()
                .optional()
                .describe('When the reading was taken, in "YYYY-MM-DD HH:MM:SS" format. Defaults to now.'),
            user_id: z.number().int().positive()
                .optional()
                .describe(`User ID of the person who took the reading. Defaults to ${config.defaultUserId} (HORTUSFOX_DEFAULT_USER_ID).`),
        },
        async ({ plant_id, value, note, taken_at, user_id }) => {
            try {
                const result = await apiPost("moisture/add", {
                    plant: plant_id,
                    value,
                    note: note ?? "",
                    taken_at: taken_at ?? "",
                    user: user_id ?? config.defaultUserId,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );
}
