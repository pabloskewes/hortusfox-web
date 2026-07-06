import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { apiGet } from "../client.js";
import { jsonResult, errorResult } from "./_util.js";

export function registerLocationTools(server: McpServer): void {
    server.tool(
        "list_locations",
        "List all locations (rooms) in the HortusFox workspace. Optionally include the plants in each location.",
        {
            include_plants: z.boolean()
                .optional()
                .describe("If true, the response includes each location's plant list and plant count."),
        },
        async ({ include_plants }) => {
            try {
                const result = await apiGet("locations/list", {
                    include_plants: include_plants ?? false,
                });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );
}
