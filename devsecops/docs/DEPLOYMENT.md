# Automated Secure Deployment

Part 2 of the project: the secure deployment strategy is fully automated by the
`deploy` job of the pipeline and can be reproduced manually at any time.

## 1. Architecture

```
                         runner host (on-premise)
 +------------------------------------------------------------------+
 |  :8080  HTTP  -----> nginx (TLS termination, secure headers)     |
 |  :8443  HTTPS ----->   | proxy_pass                              |
 |                        v                                         |
 |                      app (kimai-devsecops:local, Apache+PHP 8.3) |
 |                        |  frontend + backend networks            |
 |                        v                                         |
 |                      db (MariaDB 10.11, backend network ONLY)    |
 +------------------------------------------------------------------+

 networks: frontend (nginx<->app), backend (app<->db, internal: true)
```

- The **db** container publishes no ports and sits on an `internal: true`
  network - it is unreachable from outside the compose stack.
- The **app** container publishes no ports; only nginx is exposed.
- The image is built from the repository's own `Dockerfile`
  (`--build-arg BASE=apache`), so the deployment always matches the codebase.

## 2. Deployment automation

`devsecops/deploy/deploy.sh` (idempotent):

1. verifies `docker` + compose plugin
2. generates a self-signed TLS certificate (`nginx/generate-certs.sh`) -
   replace `nginx/certs/kimai.{crt,key}` with internal-CA certificates in real use
3. creates `.env` with a random `APP_SECRET` and random database passwords
   (never committed; `devsecops/deploy/.gitignore` blocks it)
4. builds the application image
5. starts the hardened compose stack
6. runs `doctrine:database:create` + `doctrine:migrations:migrate`
   (configuration management as code)
7. waits until the application answers over HTTPS

The pipeline executes the same script in the `deploy` job.

## 3. Security hardening (automated)

| Measure | Where | Detail |
|---|---|---|
| Debug mode disabled | compose `app` env | `APP_ENV=prod`, `APP_DEBUG=0` |
| HTTPS enforced | nginx | port 80 only redirects (301) to HTTPS; TLS 1.2/1.3 only, modern ciphers, session tickets off |
| Secure headers | nginx | HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, CSP (Report-Only - see below) |
| Sensitive endpoints/files restricted | nginx | dotfiles, `composer.*`, `.env`, `.git`, README denied; `/_profiler` not routed in prod |
| Rate limiting | nginx | 5 req/min per IP on `/login` + `/auth` (on top of Symfony's login throttling) |
| Version disclosure | nginx | `server_tokens off`, `X-Powered-By` hidden |
| Network segmentation | compose | `backend` network is `internal: true` |
| Container hardening | compose | `no-new-privileges` on all services; nginx runs read-only with dropped capabilities and tmpfs for runtime dirs |
| Secrets | deploy.sh | random `APP_SECRET`/DB passwords per environment, file mode 600, git-ignored |
| Trusted proxies | compose `app` env | private ranges only, so `X-Forwarded-*` is honored from nginx |

### Why CSP runs in Report-Only mode

The current frontend ships inline scripts and inline styles (Tabler bundle,
runtime assets). An enforcing `script-src 'self'` CSP would break the UI. The
deployment therefore emits `Content-Security-Policy-Report-Only`, which still
surfaces violations in browser consoles for review. Before switching to
enforcing mode, collect reports on a staging instance and refactor the inline
assets. The audit script accepts either CSP variant.

## 4. Post-deployment validation (automated)

Executed in order by the `deploy` job; any failure fails the pipeline and
triggers the developer notification:

| Check | Script | What it proves |
|---|---|---|
| Image vulnerability scan | Trivy image (report) | the built container carries no known HIGH/CRITICAL findings |
| Availability & integrity | `validation/smoke-test.sh` | HTTPS 200 on `/en/login`, Kimai content marker, HTTP->HTTPS redirect, API returns 401 without credentials |
| Configuration audit | `validation/security-audit.sh` | secure headers, no version leakage, cookie flags (HttpOnly/SameSite/Secure), TRACE disabled, sensitive paths blocked, no debug leakage, TLS 1.0/1.1 rejected |
| DAST | `validation/zap-baseline.sh` | OWASP ZAP passive scan; FAIL alerts fail the build, warnings are recorded in the report |

Reports are written to `/opt/kimai/security-reports/<run-id>/` on the runner.

## 5. Operating the environment

Deployment state (the generated `.env` secrets and the TLS certificate) lives
in `/opt/kimai/devsecops-deploy/` on the runner host - outside the git
checkout, which CI runners wipe between jobs. The database volume keeps the
password from the first deployment, so wiping `.env` would strand the
database with an unrecoverable credential. If the database password and the
volume are ever out of sync (e.g. after a manual cleanup), reset once:

```bash
devsecops/deploy/deploy.sh --reset-volumes   # docker compose down -v, then redeploy
```

```bash
# deploy / redeploy
devsecops/deploy/deploy.sh

# create a demo administrator (first login)
docker compose -f devsecops/deploy/docker-compose.yml exec -T app \
    bin/console kimai:user:create admin admin@example.com ROLE_SUPER_ADMIN

# logs / shell
docker compose -f devsecops/deploy/docker-compose.yml logs -f
docker compose -f devsecops/deploy/docker-compose.yml exec app bash

# tear down (keeps data volumes)
docker compose -f devsecops/deploy/docker-compose.yml down
```
