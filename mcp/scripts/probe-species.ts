import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";

const transport = new StdioClientTransport({
    command: "npx",
    args: ["tsx", "src/server.ts"],
    cwd: new URL("..", import.meta.url).pathname,
});

const client = new Client(
    { name: "probe-species", version: "0.0.1" },
    { capabilities: {} }
);

await client.connect(transport);

try {
    const r = await client.callTool({
        name: "get_species_info",
        arguments: { scientific_name: "Monstera deliciosa" },
    });
    console.log("isError:", r.isError);
    for (const b of r.content) {
        if (b.type === "text") {
            const text = b.text;
            console.log("---");
            console.log(text.length > 1200 ? text.slice(0, 1200) + "..." : text);
        }
    }
} finally {
    await client.close();
}
