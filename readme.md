# API PHP FPM - Mystock

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-009639?style=for-the-badge&logo=nginx&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Phinx](https://img.shields.io/badge/Phinx-555555?style=for-the-badge)
![Redis](https://img.shields.io/badge/Redis-FF4438?style=for-the-badge&logo=redis&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)
![Monolog](https://img.shields.io/badge/Monolog-555555?style=for-the-badge)
![Swagger](https://img.shields.io/badge/Swagger-85EA2D?style=for-the-badge&logo=swagger&logoColor=black)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)

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
CREATE DATABASE IF NOT EXISTS mystock;
GRANT ALL PRIVILEGES ON mystock.* TO '<DB_USER>'@'%';
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
