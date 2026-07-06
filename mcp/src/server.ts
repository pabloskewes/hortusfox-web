#!/usr/bin/env node
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";

import { config } from "./config.js";
import { registerLocationTools } from "./tools/locations.js";
import { registerPlantTools } from "./tools/plants.js";
import { registerTaskTools } from "./tools/tasks.js";
import { registerMoistureTools } from "./tools/moisture.js";
import { registerSpeciesTools } from "./tools/species.js";

const server = new McpServer({
    name: "hortusfox",
    version: "0.1.0",
});

registerLocationTools(server);
registerPlantTools(server);
registerTaskTools(server);
registerMoistureTools(server);
registerSpeciesTools(server);

const transport = new StdioServerTransport();
await server.connect(transport);

process.stderr.write(
    `hortusfox mcp server started, talking to ${config.apiBaseUrl}\n`
);
