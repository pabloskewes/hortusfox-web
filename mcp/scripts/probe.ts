import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";

const transport = new StdioClientTransport({
    command: "npx",
    args: ["tsx", "src/server.ts"],
    cwd: new URL("..", import.meta.url).pathname,
});

const client = new Client(
    { name: "probe", version: "0.0.1" },
    { capabilities: {} }
);

await client.connect(transport);

try {
    const tools = await client.listTools();
    console.log(`Found ${tools.tools.length} tools:`);
    for (const t of tools.tools) {
        console.log(`  - ${t.name}: ${t.description?.split("\n")[0]}`);
    }

    console.log("\n→ list_locations");
    const r1 = await client.callTool({ name: "list_locations", arguments: {} });
    for (const block of r1.content) {
        if (block.type === "text") {
            const preview = block.text.length > 400 ? block.text.slice(0, 400) + "..." : block.text;
            console.log(preview);
        }
    }

    console.log("\n→ list_plants (location=1)");
    const r2 = await client.callTool({ name: "list_plants", arguments: { location_id: 1 } });
    for (const block of r2.content) {
        if (block.type === "text") {
            const preview = block.text.length > 400 ? block.text.slice(0, 400) + "..." : block.text;
            console.log(preview);
        }
    }

    console.log("\n→ list_moisture_readings (plant_id=1)");
    const r3 = await client.callTool({ name: "list_moisture_readings", arguments: { plant_id: 1 } });
    for (const block of r3.content) {
        if (block.type === "text") {
            const preview = block.text.length > 400 ? block.text.slice(0, 400) + "..." : block.text;
            console.log(preview);
        }
    }

    console.log("\n→ get_plant (plant_id=999999, should error gracefully)");
    const r4 = await client.callTool({ name: "get_plant", arguments: { plant_id: 999999 } });
    console.log(`  isError: ${r4.isError}`);
    for (const block of r4.content) {
        if (block.type === "text") console.log(`  ${block.text}`);
    }

    console.log("\n→ add_moisture_reading (value=10, should fail Zod validation)");
    const r5 = await client.callTool({ name: "add_moisture_reading", arguments: { plant_id: 1, value: 10 } });
    console.log(`  isError: ${r5.isError}`);
    for (const block of r5.content) {
        if (block.type === "text") console.log(`  ${block.text.slice(0, 300)}`);
    }
} finally {
    await client.close();
}
