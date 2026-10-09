### MyStock API

Product catalog API, used to practice caching (cache-aside with Redis) on top of MySQL.

### How it Works
Uses Nginx as a proxy server;

Api requests (under the /api prefix) are forwarded to the PHP-FPM, with the rest of the path in `PATH_INFO`;

No framework: layers, each one calling the next: `Router` → `Controllers` → `Services` → `Repositories` → MySQL.

```
src
+-- index.php       // front controller: wires the dependencies, declares the routes, handles errors
+-- Http            // Router, Request, Response, HttpException, RequireAuth (JWT guard for a route)
+-- Controllers     // HTTP <-> service: reads path/query/body, formats the response
+-- Services        // business rules and validation
+-- Repositories    // ProductRepository and UserRepository (interfaces) and their MySQL implementations
+-- Models          // Product, User
+-- Infra           // Config (env), Database (PDO), RedisFactory (Predis), LoggerFactory (Monolog)
database
+-- migrations      // Phinx migrations; the first one creates the schema
+-- seeds           // Phinx seeders (ProductSeeder: 500 sample products)
docs                // Swagger UI (index.html) + OpenAPI spec (openapi.yaml), served by nginx
```

Repositories implement an interface so a cached implementation can wrap the MySQL one.

### Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/health` | MySQL and Redis status (200 or 503) |
| POST | `/api/auth/register` | creates a user: `{email, password}` (password with 8+ characters) → 201 `{id, email}` |
| POST | `/api/auth/login` | `{email, password}` → `{token}`, a JWT valid for 1 hour |
| GET | `/api/products?category=&page=&limit=` | paginated list, ordered by name (`limit` up to 100) |
| GET | `/api/products/{id}` | one product |
| PATCH | `/api/products/{id}` | changes `price_cents` and/or `stock` (non-negative integers); **requires a token** |

Reads are public; writes need `Authorization: Bearer <token>` (missing, invalid or expired token: 401). Tokens are HS256, signed with `JWT_SECRET_KEY`, with the user id in `sub`; passwords are stored as Argon2id hashes.

```bash
curl -s https://mystock.cloud.local/api/auth/register -H 'Content-Type: application/json' -d '{"email":"ana@example.com","password":"secret123"}'
TOKEN=$(curl -s https://mystock.cloud.local/api/auth/login -H 'Content-Type: application/json' -d '{"email":"ana@example.com","password":"secret123"}' | jq -r .token)
curl -s -X PATCH https://mystock.cloud.local/api/products/1 -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{"stock":5}'
```

Swagger UI at `/docs/` (`/` redirects there); the spec is `docs/openapi.yaml`, written by hand: update it when a route changes.

### Logs

[Monolog](https://github.com/Seldaek/monolog) writes one JSON line per event to stderr, which FPM forwards to the container log (`docker logs -f local-mystock-fpm`):
- `request` (info): method, uri, status and `duration_ms`, one per request;
- `health check failed` (warning) and `unhandled exception` (error, with stack trace).

Every line of the same request shares `extra.uid`. Set the minimum level with `LOG_LEVEL` in `etc/config/local.env` (default `info`).

### Dependencies
1. Docker
2. The [stack-containers](https://github.com/mrtrevisan/stack-containers) `core`, `infra/local-mysql` and `infra/local-redis` services running

### Running
1. Create the database (as MySQL root) and give the API user access to it:
```bash
docker exec local-mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS catalog; GRANT ALL PRIVILEGES ON catalog.* TO \`$MYSQL_USER\`@\`%\`;"'
```

2. Configure and start:
```bash
cp etc/config/example.env etc/config/local.env   # fill in DB_*, REDIS_* and JWT_SECRET_KEY (openssl rand -base64 48)
docker compose up -d --build
```

3. Run the migrations and load 500 sample products (the seed recreates the data on every run), with [Phinx](https://phinx.org) inside the FPM container:
```bash
docker exec local-mystock-fpm vendor/bin/phinx migrate
docker exec local-mystock-fpm vendor/bin/phinx seed:run
```

New migration: `docker exec local-mystock-fpm vendor/bin/phinx create MyChange` (the file appears in `database/migrations`, mounted in the container). Undo the last one with `phinx rollback`.

The API answers at `https://mystock.cloud.local/api` and the docs at `https://mystock.cloud.local/docs/` (via Traefik; no port is published on the host).
