# HortusFox MCP server

A thin [Model Context Protocol](https://modelcontextprotocol.io/) (MCP) server
that wraps the existing HortusFox REST API and exposes 10 tools for
LLM-driven conversational access to plant data. Lives in `hortusfox-web/mcp/`.

This server is the action surface for the (planned) in-app LLM chat feature
and is also useful ad-hoc from any MCP-aware client such as `pi`.

## Tools exposed

| Tool | API endpoint |
|---|---|
| `list_locations` | `GET /api/locations/list` |
| `list_plants` | `GET /api/plants/list` |
| `get_plant` | `GET /api/plants/get` |
| `add_plant` | `POST /api/plants/add` |
| `update_plant` | `POST /api/plants/update` |
| `list_tasks` | `GET /api/tasks/fetch` |
| `add_task` | `POST /api/tasks/add` |
| `list_moisture_readings` | `GET /api/moisture/fetch` |
| `add_moisture_reading` | `POST /api/moisture/add` |
| `search_plants` | `GET /api/plants/search` |

The full parameter schema is auto-discovered by MCP clients via tool listing.

## Setup

```sh
cd mcp
npm install
cp .env.example .env
# Edit .env and set HORTUSFOX_API_TOKEN to a real token from the admin dashboard
```

The server reads `mcp/.env` at startup. The path is resolved relative to the
server's own location, so it works regardless of the cwd that launched it.

## Run it

```sh
cd mcp
npm start            # one-shot, for foreground use
npm run dev          # tsx watch mode, restarts on file changes
```

Both commands speak MCP over stdio. They are meant to be launched by an MCP
client, not run interactively.

## Use it with pi

The repo ships with a `.mcp.json` at the project root. Pi picks it up
automatically when run from `hortusfox-web/`:

```sh
cd hortusfox-web
pi
```

Inside pi:

```
/mcp tools            # should list the 10 hortusfox tools
/mcp reconnect        # (only if the server didn't connect at startup)
```

Then chat normally. Try:

> "qué plantas tengo en Living Room"

Pi should call `list_locations` and `list_plants` and answer with the data.

## Use it with the MCP inspector (low-level smoke test)

```sh
cd mcp
npx @modelcontextprotocol/inspector npm start
```

The inspector opens a web UI where you can list tools, call them one at a
time, and inspect raw JSON-RPC. Useful when a tool fails and you want to see
the exact request/response.

## Dev probe (CLI smoke test, no browser)

`scripts/probe.ts` spawns the server via the SDK's stdio client and runs
a fixed battery of calls (list tools, list locations/plants/readings, plus
two error cases). No browser, no UI, just stdout. Useful for CI or quick
sanity checks.

```sh
cd mcp
npx tsx scripts/probe.ts
```

## Configuration

`mcp/.env` (gitignored):

- `HORTUSFOX_API_BASE_URL` — base URL of the HortusFox API. No trailing slash.
- `HORTUSFOX_API_TOKEN` — API token, same as the one used by the web UI's
  REST client.
- `HORTUSFOX_DEFAULT_USER_ID` — user ID injected into `add_moisture_reading`
  when the LLM does not pass one. Defaults to 1 (the first admin).

## Why stdio

stdIO is the simplest MCP transport: the host launches the server as a
subprocess and they talk over stdin/stdout. Zero infra, zero ports, zero
auth surface. The trade-off is the host must be local to the server, which
is exactly our use case (the Mac).

## Why not Claude / Cursor / etc.

The tool surface is the standard MCP format, so any MCP host will work.
This project is tested with `pi` because `pi` is the engine planned for the
in-app LLM chat feature.
