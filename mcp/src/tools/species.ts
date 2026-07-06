import type { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { apiGet } from "../client.js";
import { jsonResult, errorResult } from "./_util.js";

export function registerSpeciesTools(server: McpServer): void {
    server.tool(
        "get_species_info",
        `Look up care data for a plant species (watering, light, humidity, soil, temperature ranges). Cached locally: the first call per species fetches from OpenPlantBook, subsequent calls return the cached data within 30 days.

The response includes a "cache" field: "hit" (served from cache), "miss" (fetched from OpenPlantBook and cached), or "stale" (cache existed but was past TTL; refetched and updated).

Care data comes from OpenPlantBook and is best-effort: if the species is unknown there, the call will fail with a clear error.`,
        {
            scientific_name: z.string().min(1)
                .describe("Scientific (Latin) name of the plant, e.g. 'Monstera deliciosa'. Case-insensitive."),
        },
        async ({ scientific_name }) => {
            try {
                const result = await apiGet("species/info", { scientific_name });
                return jsonResult(result);
            } catch (err) {
                return errorResult(err);
            }
        }
    );
}
