# Streamable HTTP Transport Implementation of MCP Server with Vanilla PHP

This is a minimal PHP implementation example of an MCP (Model Context Protocol) server using Streamable HTTP Transport, without any frameworks or even Composer.

## How to Start the MCP Server

After cloning this repository, execute the following commands.

The endpoint will be `http://localhost:<your configured port>/mcp`.

### Starting with Docker

Create a `.env` file and set your desired port number to `HTTP_PORT`.

```shell
cp .env.example .env
vim .env
```

Then run the following:

```shell
docker compose up
```

### Starting with PHP (>= 8.0) Installed on Your Host Machine

```shell
php -S localhost:<your desired port> mcp.php
```

## How to check if it's working

Use an MCP client that supports Streamable HTTP Transport.
