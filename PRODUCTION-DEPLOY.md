# Backup Manager — Production Deployment

This guide takes you from a fresh Ubuntu 22.04 server to a running Backup Manager instance.
Use `deploy.sh` to automate most steps, or follow them manually.

---

## Prerequisites

### Server
- Ubuntu 22.04 LTS (or 20.04)
- Root or sudo access
- SSH key already added (`~/.ssh/authorized_keys`)
- Domain pointing at the server's IP (or be ready to use the IP directly first)

### Required software (deploy.sh installs all of this)

| Software | Minimum version |
|---|---|
| PHP | 8.2 |
| PHP extensions | pdo, pdo_mysql, mbstring, openssl, zip, bcmath, curl, xml, gd, intl, fileinfo |
| Composer | 2.x |
| Node.js | 18.x |
| npm | bundled with Node |
| MySQL | 8.0 (or MariaDB 10.6+) |
| Apache | 2.4 (with mod_rewrite) |

---

## Step-by-step

### 1 — Install server dependencies

```bash
apt update && apt upgrade -y
apt install -y software-properties-common curl git unzip

# PHP 8.2
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl \
    php8.2-fileinfo

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Node 18
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Apache
apt install -y apache2
a2enmod rewrite headers
```

### 2 — MySQL: create database and user

```bash
mysql -u root -p <<'SQL'
CREATE DATABASE backup_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'backup_manager'@'localhost' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON backup_manager.* TO 'backup_manager'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 3 — Clone the repository

```bash
cd /var/www
git clone https://github.com/YOUR_USER/backup-manager.git backup-manager
cd backup-manager
```

Or upload via rsync from your Windows machine:
```bash
# Run this on Windows (Git Bash):
rsync -avz --exclude='.git' --exclude='vendor' --exclude='node_modules' \
  /c/laragon/www/backup-manager/ root@YOUR_SERVER_IP:/var/www/backup-manager/
```

### 4 — Install PHP dependencies

```bash
cd /var/www/backup-manager
composer install --no-dev --optimize-autoloader
```

### 5 — Configure environment

```bash
cp .env.production.example .env
nano .env   # fill in APP_URL, DB_PASSWORD, MAIL_* etc.

php artisan key:generate   # sets APP_KEY in .env automatically
```

### 6 — Run database migrations

```bash
php artisan migrate --force
```

### 7 — Build frontend assets

```bash
npm install
npm run build
```

### 8 — Storage symlink + permissions

```bash
php artisan storage:link

chown -R www-data:www-data /var/www/backup-manager
chmod -R 755 /var/www/backup-manager
chmod -R 775 /var/www/backup-manager/storage
chmod -R 775 /var/www/backup-manager/bootstrap/cache
```

### 9 — Create the first admin user

```bash
php artisan make:filament-user
# Follow the prompts: name, email, password
# Panel URL: https://yourdomain.com/admin
```

### 10 — Apache vhost

Create `/etc/apache2/sites-available/backup-manager.conf`:

```apache
<VirtualHost *:80>
    ServerName backup.yourdomain.com
    DocumentRoot /var/www/backup-manager/public

    <Directory /var/www/backup-manager/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/backup-manager-error.log
    CustomLog ${APACHE_LOG_DIR}/backup-manager-access.log combined
</VirtualHost>
```

```bash
a2ensite backup-manager
a2dissite 000-default   # optional: disable default site
systemctl reload apache2
```

**HTTPS (Let's Encrypt):**
```bash
apt install -y certbot python3-certbot-apache
certbot --apache -d backup.yourdomain.com
```

### 11 — Nginx alternative (if you prefer Nginx over Apache)

```nginx
server {
    listen 80;
    server_name backup.yourdomain.com;
    root /var/www/backup-manager/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/backup-manager /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d backup.yourdomain.com
```

### 12 — Scheduler (systemd timer — recommended)

The scheduler runs site monitoring (every 5 min), invoice overdue checks (daily),
and agent stale detection (every 15 min).

**`/etc/systemd/system/backup-manager-scheduler.service`:**
```ini
[Unit]
Description=Backup Manager Laravel Scheduler

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/backup-manager
ExecStart=/usr/bin/php artisan schedule:run
StandardOutput=append:/var/log/backup-manager-scheduler.log
StandardError=append:/var/log/backup-manager-scheduler.log
```

**`/etc/systemd/system/backup-manager-scheduler.timer`:**
```ini
[Unit]
Description=Run Backup Manager scheduler every minute

[Timer]
OnBootSec=60
OnUnitActiveSec=60

[Install]
WantedBy=timers.target
```

```bash
systemctl daemon-reload
systemctl enable --now backup-manager-scheduler.timer
```

### 13 — Queue worker (optional — only needed if QUEUE_CONNECTION=database)

Skip this if you keep `QUEUE_CONNECTION=sync` (the default). Only needed if you
add queued jobs later.

**`/etc/systemd/system/backup-manager-worker.service`:**
```ini
[Unit]
Description=Backup Manager Laravel Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/backup-manager
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=append:/var/log/backup-manager-worker.log
StandardError=append:/var/log/backup-manager-worker.log

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable --now backup-manager-worker
```

---

## SSH key for VPS commands

The panel's "Comandos SSH" feature reads `ssh_key_path` from each Server record.
On production this path must point to a key on the **production server** (not your
Windows machine). Recommended location: `/home/www-data/.ssh/ateneya_vps_key`.

```bash
mkdir -p /home/www-data/.ssh
# Copy ateneya_vps_key from your Windows machine:
#   scp C:\Users\André Mendes\.ssh\ateneya_vps_key root@SERVER:/home/www-data/.ssh/
chmod 600 /home/www-data/.ssh/ateneya_vps_key
chown -R www-data:www-data /home/www-data/.ssh
```

Then update the `ssh_key_path` column in the servers table (or edit each Server
record in the admin panel) to `/home/www-data/.ssh/ateneya_vps_key`.

---

## What remains after this guide

Once the server is provisioned and this script has run, the only remaining steps are:

1. **DNS** — point your domain's A record at the server IP (TTL propagation: up to 24h)
2. **HTTPS** — run `certbot --apache -d yourdomain.com` once DNS resolves
3. **SSH key** — copy `ateneya_vps_key` to the server and update Server records (see above)
4. **Seed data** — optionally re-run seeders: `php artisan db:seed` (uses placeholder
   data; skip on production and enter real data via the panel)

---

## Updating (after initial deploy)

```bash
cd /var/www/backup-manager
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
systemctl restart backup-manager-worker   # if using queue worker
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| 500 error on first load | Check `storage/logs/laravel.log`; often missing `APP_KEY` or wrong DB password |
| Assets not loading | Run `npm run build` then `php artisan view:clear` |
| Scheduler not running | `systemctl status backup-manager-scheduler.timer`; check log at `/var/log/backup-manager-scheduler.log` |
| SSH commands fail from panel | Verify `ssh_key_path` on server record; check key permissions (`chmod 600`) |
| "Page expired" on form submit | `SESSION_DRIVER=file` needs `storage/framework/sessions` writable by `www-data` |
