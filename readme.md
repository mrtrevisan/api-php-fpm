# API PHP FPM - Mystock

## Product

Product catalog: lists and shows products and lets authenticated users change price and stock.

## Concepts

- Caching with Redis: cache-aside + invalidation (in progress)
- Layered architecture (`Router` → `Controllers` → `Services` → `Repositories`)
- nginx + PHP-FPM (FastCGI)
- Network isolation (FPM only on the `internal` network)
- Stateless authentication with JWT
- Password hashing (Argon2id)
- Health check with dependency status
- Structured logging (Monolog) with a per-request id
- Schema migrations and seeds (Phinx)
- API contract with OpenAPI / Swagger UI

## Running

Requires Docker and the [stack-containers](https://github.com/mrtrevisan/stack-containers) `core`, `infra/local-mysql` and `infra/local-redis` services running.

1. Create the env file:
```bash
cp ./etc/config/example.env ./etc/config/local.env
```

2. Set the environment variables. `DB_*`, `REDIS_*` and `JWT_SECRET_KEY` (32+ bytes) are required. Generate the JWT secret with:
```bash
openssl rand -base64 48
```

3. Create the database and give the API user access to it (as MySQL root; e.g. in phpMyAdmin or `docker exec -it local-mysql mysql -uroot -p`). Tables are created by the migrations:
```sql
CREATE DATABASE IF NOT EXISTS catalog;
GRANT ALL PRIVILEGES ON catalog.* TO '<DB_USER>'@'%';
```

4. Run:
```bash
docker compose up -d --build
```

5. Run the migrations and load the sample products (the seed recreates the data on every run):
```bash
docker exec local-mystock-fpm vendor/bin/phinx migrate
docker exec local-mystock-fpm vendor/bin/phinx seed:run
```

The API answers at `https://mystock.cloud.local/api` and the docs at `https://mystock.cloud.local/docs/`.

## Endpoints

| Method | Path | Auth |
|---|---|---|
| GET | `/api/health` | public |
| POST | `/api/auth/register` `{email, password}` | public |
| POST | `/api/auth/login` `{email, password}` → `{token}` | public |
| GET | `/api/products?category=&page=&limit=` | public |
| GET | `/api/products/{id}` | public |
| PATCH | `/api/products/{id}` `{price_cents?, stock?}` | Bearer |
