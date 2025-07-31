# PHP openswoole API with PostgreSQL

A simple PHP openswoole API application and PostgreSQL, containerized with Docker.


## Technology Stack

**PHP openswoole Container: FROM php:8.4-fpm-alpine**
- OS Alpine Linux: 3.22.0
- PHP: 8.4.8
- PDO
- pdo_mysql
- OPcache
- openswoole: 25.2.0

**PostgreSQL Container: FROM postgres:14.18**
- OS Debian GNU/Linux 12 (bookworm): 12
- PostgreSQL: 14.18

**Adminer Container: FROM adminer:5-standalone**
- OS Alpine Linux: 3.22.1
- Adminer: 5.3.0

**Grafana/k6 Container: FROM grafana/k6:1.1.0**
- OS Alpine Linux: 3.22.0
- grafana/k6: 1.1.0


## Getting Started

### 1. Clone the Repository
```bash
git clone https://github.com/opsnoopop/api_php_openswoole_postgresql.git
```

### 2. Navigate to Project Directory
```bash
cd api_php_openswoole_postgresql
```

### 3. Start the Application
```bash
docker compose up -d --build
```

### 4. Create table users
```bash
docker exec -i container_postgresql sh -c "PGPASSWORD='testpass' psql -U testuser -d testdb -c '
CREATE TABLE IF NOT EXISTS public.users (
  user_id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
'"
```


## API Endpoints

### Health Check
- **URL:** http://localhost:9501/
- **Method:** GET
- **Response:**
```json
{
  "message": "Hello World from PHP openswoole"
}
```

### Create user
- **URL:** http://localhost:9501/users
- **Method:** POST
- **Request**
```json
{
  "username":"optest",
  "email":"auttakorn.w@clicknext.com"
}
```
- **Response:**
```json
{
  "message":"User created successfully",
  "user_id":1
}
```

### Get user
- **URL:** http://localhost:9501/users/1
- **Method:** GET
- **Response:**
```json
{
  "user_id":1,
  "username":"optest",
  "email":"auttakorn.w@clicknext.com"
}
```


## Test Performance by grafana/k6

### grafana/k6 test Health Check
```bash
docker run \
--name container_k6 \
--rm \
-it \
--network global_php_openswoole \
-v ./k6/:/k6/ \
grafana/k6:1.1.0 \
run /k6/k6_php_openswoole_health_check.js
```

### grafana/k6 test Insert Create user
```bash
docker run \
--name container_k6 \
--rm \
-it \
--network global_php_openswoole \
-v ./k6/:/k6/ \
grafana/k6:1.1.0 \
run /k6/k6_php_openswoole_create_user.js
```

### grafana/k6 test Select Get user by id
```bash
docker run \
--name container_k6 \
--rm \
-it \
--network global_php_openswoole \
-v ./k6/:/k6/ \
grafana/k6:1.1.0 \
run /k6/k6_php_openswoole_get_user_by_id.js
```

### check entrypoint grafana/k6
```bash
docker run \
--name container_k6 \
--rm \
-it \
--entrypoint \
/bin/sh grafana/k6:1.1.0
```


## Stop the Application

### Truncate table users
```bash
docker exec -i container_postgresql sh -c "PGPASSWORD='testpass' psql -U testuser -d testdb -c '
Truncate testdb.users;
"
```

### Delete table users
```bash
docker exec -i container_postgresql sh -c "PGPASSWORD='testpass' psql -U testuser -d testdb -c '
DELETE FROM testdb.users;
"
```

### Stop the Application
```bash
docker compose down
```