# 🚀 Deploying to Production (time.mountaindev.com)

## ⚡ Simple Docker Deployment (Recommended)

### One-Time Setup on Production

```bash
# SSH to production server
ssh time.mountaindev.com

# Clone your repo (or cd to existing directory)
cd /var/www/kimai  # or wherever you want it

# Create production compose file if needed
# Use docker-compose.yml for production

# Start it up
docker compose up -d
```

### Deploy Updates (Every Time)

**On your local machine:**

```bash
# Commit your changes (composer.json already has the plugin)
git add .
git commit -m "Update"
git push origin main
```

**On production server:**

```bash
# Just pull and rebuild - that's it!
git pull && docker compose up -d --build
```

Done! 🎉 The Docker build process automatically runs `composer install`, so any plugins in `composer.json` are installed automatically.

---

## 🔍 How It Works

When you run `docker compose up -d --build`:

1. **Pulls latest code** from your git repo (in the image)
2. **Runs `composer install`** (installs all packages including plugins)
3. **Builds the Docker image** with everything configured
4. **Starts the container** with your app ready to go

The plugin is in your `composer.json`, so it's automatically installed every time the image builds.

---

## 📋 Full Setup Guide

### Local Development (What You Did)

```bash
# 1. Start local Docker
docker compose -f docker-compose.dev.yml up -d

# 2. Install plugin (already done)
docker compose -f docker-compose.dev.yml exec kimai bash -c "cd /opt/kimai && composer require keleo/customer-portal"

# 3. Copy composer files to host (already done)
docker cp kimai-kimai-1:/opt/kimai/composer.json ./composer.json
docker cp kimai-kimai-1:/opt/kimai/composer.lock ./composer.lock
```

### Production Deployment

```bash
# 1. Commit everything
git add composer.json composer.lock docker-compose.yml SETUP.md DEPLOYMENT.md
git commit -m "Add Customer Portal plugin and Docker setup"
git push origin main

# 2. SSH to production
ssh time.mountaindev.com
cd /var/www/kimai

# 3. Deploy
git pull && docker compose up -d --build
```

That's it! No manual plugin installation needed.

---

## 🗄️ Database Setup on Production

**First time only:**

```bash
# Import your production database
docker compose exec -T db mysql -u kimai -pkimai kimai < kimai.sql

# Or if you're migrating from existing server:
mysqldump -u root -p kimai > backup.sql
docker compose exec -T db mysql -u kimai -pkimai kimai < backup.sql
```

---

## 🌐 DNS & Web Server Setup

**Option 1: Direct Port Mapping**

```yaml
# In docker-compose.yml, change ports to:
ports:
  - "80:8001"
  - "443:8001"
```

**Option 2: Nginx Reverse Proxy (Better)**

```nginx
# /etc/nginx/sites-available/kimai
server {
    listen 80;
    server_name time.mountaindev.com;

    location / {
        proxy_pass http://localhost:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Then:

```bash
sudo ln -s /etc/nginx/sites-available/kimai /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔄 Daily Workflow

### Making Changes

```bash
# Local: Edit code, test in Docker
docker compose -f docker-compose.dev.yml up -d

# Commit and push
git add .
git commit -m "Your changes"
git push

# Production: Deploy (literally one command)
ssh time.mountaindev.com "cd /var/www/kimai && git pull && docker compose up -d --build"
```

Or SSH in and run:

```bash
git pull && docker compose up -d --build
```

### Adding New Plugins

```bash
# Local: Add plugin in Docker
docker compose -f docker-compose.dev.yml exec kimai bash -c "cd /opt/kimai && composer require plugin/name"

# Copy composer files
docker cp kimai-kimai-1:/opt/kimai/composer.json ./composer.json
docker cp kimai-kimai-1:/opt/kimai/composer.lock ./composer.lock

# Commit
git add composer.json composer.lock
git commit -m "Add new plugin"
git push

# Production: Deploy
ssh time.mountaindev.com "cd /var/www/kimai && git pull && docker compose up -d --build"
```

---

## 🎯 Why This Is Better

| Traditional Setup           | Docker Setup   |
| --------------------------- | -------------- |
| `git pull`                  | `git pull`     |
| `composer install`          | _(automatic)_  |
| `bin/console cache:clear`   | _(automatic)_  |
| `chown/chmod fixes`         | _(not needed)_ |
| `systemctl restart apache2` | _(automatic)_  |
| Manual plugin installation  | _(automatic)_  |
| **5-6 commands**            | **1 command**  |

---

## 🔐 Environment Variables

Create `.env` on production for sensitive settings:

```bash
# On production server
cat > /var/www/kimai/.env << EOF
DATABASE_URL=mysql://kimai:kimai@db/kimai?charset=utf8mb4&serverVersion=8.4
APP_SECRET=$(openssl rand -base64 32)
MAILER_FROM=noreply@mountaindev.com
APP_ENV=prod
EOF
```

Docker Compose will automatically use this file.

---

## 🆘 Troubleshooting

**Check if containers are running:**

```bash
docker compose ps
```

**View logs:**

```bash
docker compose logs -f
```

**Restart everything:**

```bash
docker compose restart
```

**Nuclear option (fresh start):**

```bash
docker compose down -v
docker compose up -d --build
# Re-import database
```

**Check plugin is installed:**

```bash
docker compose exec kimai /opt/kimai/bin/console kimai:plugins
```

---

## 📊 Production Checklist

- [ ] Committed `composer.json` and `composer.lock`
- [ ] Pushed to git
- [ ] Set up `.env` on production with correct values
- [ ] Database imported
- [ ] DNS pointing to production server
- [ ] SSL certificate installed (Let's Encrypt recommended)
- [ ] Firewall configured (ports 80, 443)
- [ ] Backups configured for database

---

## 🚀 Quick Reference

```bash
# Deploy updates
git pull && docker compose up -d --build

# View logs
docker compose logs -f

# Restart
docker compose restart

# Stop
docker compose down

# Access database
docker compose exec db mysql -u kimai -pkimai kimai

# Run Kimai console command
docker compose exec kimai /opt/kimai/bin/console [command]
```

---

**That's it!** Just `git pull && docker compose up -d --build` to deploy. Everything else is automatic! 🎉
