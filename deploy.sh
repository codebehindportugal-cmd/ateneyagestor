#!/usr/bin/env bash
# deploy.sh — one-shot Backup Manager deploy for a fresh Ubuntu 22.04 + Apache server.
# Run as root: bash deploy.sh
# Nothing is deployed automatically — this is a local prep artifact.
# Adjust the variables below before running on a real server.

set -euo pipefail

# ── Config — edit before running ────────────────────────────────────────────
APP_DOMAIN="backup.yourdomain.com"   # your domain or server IP
APP_DIR="/var/www/backup-manager"
REPO_URL=""                           # git remote URL, or leave empty to skip clone (if you rsync'd)
DB_NAME="backup_manager"
DB_USER="backup_manager"
DB_PASS=""                            # set a strong password here
# ────────────────────────────────────────────────────────────────────────────

if [[ $EUID -ne 0 ]]; then
    echo "Run as root: sudo bash deploy.sh" >&2
    exit 1
fi

if [[ -z "$DB_PASS" ]]; then
    echo "ERROR: DB_PASS is empty. Edit the variables at the top of this script." >&2
    exit 1
fi

echo "==> Installing system packages..."
apt-get update -q
apt-get install -y -q software-properties-common curl git unzip

# PHP 8.2
add-apt-repository ppa:ondrej/php -y
apt-get update -q
apt-get install -y -q \
    php8.2 php8.2-cli php8.2-fpm php8.2-mysql \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip \
    php8.2-bcmath php8.2-gd php8.2-intl php8.2-fileinfo

# Composer
if ! command -v composer &>/dev/null; then
    echo "==> Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Node 18
if ! command -v node &>/dev/null; then
    echo "==> Installing Node 18..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y -q nodejs
fi

# Apache
echo "==> Configuring Apache..."
apt-get install -y -q apache2
a2enmod rewrite headers
systemctl enable apache2

# MySQL
echo "==> Configuring MySQL..."
if ! command -v mysql &>/dev/null; then
    apt-get install -y -q mysql-server
    systemctl enable mysql
    systemctl start mysql
fi

mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ── App ─────────────────────────────────────────────────────────────────────
if [[ -n "$REPO_URL" ]]; then
    echo "==> Cloning repository..."
    rm -rf "$APP_DIR"
    git clone "$REPO_URL" "$APP_DIR"
elif [[ ! -d "$APP_DIR" ]]; then
    echo "ERROR: $APP_DIR does not exist and REPO_URL is empty." >&2
    echo "Either set REPO_URL or rsync the project there first." >&2
    exit 1
fi

cd "$APP_DIR"

echo "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --quiet

echo "==> Installing and building frontend assets..."
npm install --silent
npm run build

# ── Environment ─────────────────────────────────────────────────────────────
if [[ ! -f .env ]]; then
    echo "==> Creating .env from example..."
    cp .env.production.example .env

    # Patch the values we know
    sed -i "s|APP_URL=.*|APP_URL=https://${APP_DOMAIN}|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env

    php artisan key:generate
    echo ""
    echo "  .env created. Edit /var/www/backup-manager/.env to set MAIL_* before going live."
fi

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding VPS servers (firstOrCreate -- safe to re-run)..."
php artisan db:seed --class=VpsServerSeeder --force

echo "==> Creating storage symlink..."
php artisan storage:link || true

echo "==> Caching config/routes/views for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Permissions ─────────────────────────────────────────────────────────────
echo "==> Setting permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# ── Apache vhost ─────────────────────────────────────────────────────────────
VHOST_FILE="/etc/apache2/sites-available/backup-manager.conf"
echo "==> Writing Apache vhost to $VHOST_FILE..."
cat > "$VHOST_FILE" <<VHOST
<VirtualHost *:80>
    ServerName ${APP_DOMAIN}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/backup-manager-error.log
    CustomLog \${APACHE_LOG_DIR}/backup-manager-access.log combined
</VirtualHost>
VHOST

a2ensite backup-manager
a2dissite 000-default 2>/dev/null || true
systemctl reload apache2

# ── Scheduler (systemd timer) ────────────────────────────────────────────────
echo "==> Installing scheduler systemd units..."
cat > /etc/systemd/system/backup-manager-scheduler.service <<UNIT
[Unit]
Description=Backup Manager Laravel Scheduler

[Service]
Type=oneshot
User=www-data
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan schedule:run
StandardOutput=append:/var/log/backup-manager-scheduler.log
StandardError=append:/var/log/backup-manager-scheduler.log
UNIT

cat > /etc/systemd/system/backup-manager-scheduler.timer <<TIMER
[Unit]
Description=Run Backup Manager scheduler every minute

[Timer]
OnBootSec=60
OnUnitActiveSec=60

[Install]
WantedBy=timers.target
TIMER

systemctl daemon-reload
systemctl enable --now backup-manager-scheduler.timer

# ── Queue worker (systemd service) ───────────────────────────────────────────
# The default QUEUE_CONNECTION=sync means no worker is required right now --
# jobs run inline. If you switch to QUEUE_CONNECTION=database later, enable
# this service so queued jobs are actually processed.
#
# To activate: uncomment the block below and run:
#   systemctl daemon-reload
#   systemctl enable --now backup-manager-queue.service
#
cat > /etc/systemd/system/backup-manager-queue.service <<QUNIT
[Unit]
Description=Backup Manager Laravel Queue Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=append:/var/log/backup-manager-queue.log
StandardError=append:/var/log/backup-manager-queue.log

[Install]
WantedBy=multi-user.target
QUNIT
systemctl daemon-reload
# systemctl enable --now backup-manager-queue.service  # uncomment when QUEUE_CONNECTION=database

# ── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo "=========================================="
echo " Backup Manager deployed to ${APP_DIR}"
echo "=========================================="
echo ""
echo " Next steps:"
echo "  1. Verify .env settings: nano ${APP_DIR}/.env"
echo "  2. Create admin user:    cd ${APP_DIR} && php artisan make:filament-user"
echo "  3. HTTPS:                certbot --apache -d ${APP_DOMAIN}"
echo "  4. Copy SSH key:         scp ateneya_vps_key root@<server>:/home/www-data/.ssh/"
echo "                           chmod 600 /home/www-data/.ssh/ateneya_vps_key"
echo "                           chown -R www-data:www-data /home/www-data/.ssh"
echo "  5. Update ssh_key_path in admin panel to /home/www-data/.ssh/ateneya_vps_key"
echo ""
echo " Panel: https://${APP_DOMAIN}/admin"
echo ""
