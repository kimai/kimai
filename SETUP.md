# 🐳 Local Kimai Development Setup

Quick guide to run your EC2 Kimai instance locally using Docker.

## ⚡ Quick Start

```bash
# Start Kimai
docker compose -f docker-compose.dev.yml up -d

# View logs
docker compose -f docker-compose.dev.yml logs -f

# Open browser to http://localhost:8001
# Login with your production credentials
```

## 📋 Prerequisites

- Docker Desktop (✅ installed)
- Your production database dump

## 🗄️ Load Production Database

```bash
# Import your database
docker compose -f docker-compose.dev.yml exec -T db mysql -u kimai -pkimai kimai < kimai.sql

# Restart Kimai
docker compose -f docker-compose.dev.yml restart kimai
```

## 🔌 Installing Plugins

Customer Portal (or any Kimai plugin):

```bash
# 1. Copy plugin ZIP to packages directory
docker cp CustomerPortalBundle-4.5.0.zip kimai-kimai-1:/opt/kimai/var/packages/

# 2. Install via composer
docker compose -f docker-compose.dev.yml exec kimai bash -c "cd /opt/kimai && composer require keleo/customer-portal"

# 3. Clear cache
docker compose -f docker-compose.dev.yml exec kimai bash -c "cd /opt/kimai && bin/console cache:clear"

# 4. Restart
docker compose -f docker-compose.dev.yml restart kimai
```

## 🔄 Common Commands

```bash
# Start containers
docker compose -f docker-compose.dev.yml up -d

# Stop containers
docker compose -f docker-compose.dev.yml down

# View logs
docker compose -f docker-compose.dev.yml logs -f kimai

# Restart Kimai
docker compose -f docker-compose.dev.yml restart kimai

# Access MySQL shell
docker compose -f docker-compose.dev.yml exec db mysql -u kimai -pkimai kimai

# Run Kimai console commands
docker compose -f docker-compose.dev.yml exec kimai /opt/kimai/bin/console [command]

# List installed plugins
docker compose -f docker-compose.dev.yml exec kimai /opt/kimai/bin/console kimai:plugins
```

## 🔧 Development Workflow

1. **Make code changes** in your local files
2. **Rebuild container** (if needed):
   ```bash
   docker compose -f docker-compose.dev.yml up -d --build
   ```
3. **Test at** http://localhost:8001
4. **Deploy to EC2** when ready:
   ```bash
   git add .
   git commit -m "Your changes"
   git push origin main
   # Then deploy on EC2
   ```

## 🆘 Troubleshooting

**Port 8001 already in use?**

```bash
# Edit docker-compose.dev.yml, change:
# ports: - "8080:8001"
```

**Reset everything:**

```bash
docker compose -f docker-compose.dev.yml down -v
docker compose -f docker-compose.dev.yml up -d
# Re-import database
```

**Check container status:**

```bash
docker compose -f docker-compose.dev.yml ps
```

**View all logs:**

```bash
docker compose -f docker-compose.dev.yml logs
```

## 📦 Database Backup/Restore

```bash
# Backup database
docker compose -f docker-compose.dev.yml exec db mysqldump -u kimai -pkimai kimai > backup.sql

# Restore database
docker compose -f docker-compose.dev.yml exec -T db mysql -u kimai -pkimai kimai < backup.sql
```

## 📝 Important Notes

- **Plugins** are stored in Docker volumes (persist between restarts)
- **Configuration** from production database is preserved
- **Port 8001** for Kimai, **3306** for MySQL
- **Two compose files:**
  - `docker-compose.yml` - Official image (for quick testing)
  - `docker-compose.dev.yml` - Builds from your code (for development)

## 🔑 Access

- **Kimai:** http://localhost:8001
- **MySQL:** localhost:3306
  - Database: `kimai`
  - Username: `kimai`
  - Password: `kimai`

---

## 🚀 Deploying to Production

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for complete guide on deploying to time.mountaindev.com

---

**Need more help?** Check [Kimai Docs](https://www.kimai.org/documentation/) or [Docker Docs](https://docs.docker.com/)
