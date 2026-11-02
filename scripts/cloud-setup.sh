#!/usr/bin/env bash
# Setup of a fresh Linux machine for this project — Claude Code cloud sessions (claude.ai/code)
# or any new Ubuntu 24.04 box. Safe to run again: each step is skipped when already done.
#
#   bash scripts/cloud-setup.sh
#
# Gives: PHP 8.4 + extensions, Composer, Node (.nvmrc), MySQL 8 with the databases
# university (dev) and university_test (Pest), .env, a migrated and seeded dev database.
# Not available in the cloud: the legacy DIU database (project_diu), the FMS token, the DIU
# frontend copy — work that needs them waits for the owner's machine.
set -euo pipefail
cd "$(dirname "$0")/.."

SUDO=$([ "$(id -u)" = 0 ] && echo "" || echo "sudo")
DB_USER=uwms
DB_PASS=uwms
step() { printf '\n== %s\n' "$1"; }

step "PHP 8.4"
if ! php -r 'exit(version_compare(PHP_VERSION, "8.3", ">=") ? 0 : 1);' 2>/dev/null; then
    $SUDO apt-get update -y
    $SUDO apt-get install -y software-properties-common
    $SUDO add-apt-repository -y ppa:ondrej/php
    $SUDO apt-get update -y
    $SUDO apt-get install -y php8.4-cli
fi
PHPV=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
missing=()
for ext in intl gd zip bcmath exif pdo_mysql mbstring xml curl; do
    php -m | grep -qi "^${ext}$" || missing+=("php${PHPV}-${ext/pdo_mysql/mysql}")
done
if [ ${#missing[@]} -gt 0 ]; then
    $SUDO apt-get update -y && $SUDO apt-get install -y "${missing[@]}"
fi
php -v | head -1

step "Composer"
if ! command -v composer >/dev/null; then
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    $SUDO php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
fi
composer --version

step "Node $(cat .nvmrc)"
if ! command -v node >/dev/null || [ "$(node -v | cut -d. -f1 | tr -d v)" -lt "$(cat .nvmrc)" ]; then
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] || curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
    # shellcheck disable=SC1091
    . "$NVM_DIR/nvm.sh"
    nvm install "$(cat .nvmrc)"
    nvm use "$(cat .nvmrc)"
fi
node -v

step "MySQL"
if ! command -v mysqld >/dev/null; then
    $SUDO apt-get update -y && $SUDO DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
fi
if ! mysqladmin ping --silent 2>/dev/null; then
    $SUDO service mysql start || ($SUDO mkdir -p /var/run/mysqld && $SUDO chown mysql:mysql /var/run/mysqld && $SUDO mysqld_safe --user=mysql >/dev/null 2>&1 &)
    for _ in $(seq 1 30); do mysqladmin ping --silent 2>/dev/null && break; sleep 1; done
fi
$SUDO mysql -e "
    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
    CREATE DATABASE IF NOT EXISTS university CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS university_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS university_legacy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'localhost';
    GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'127.0.0.1';
    FLUSH PRIVILEGES;"

step "Dependencies"
composer install --no-interaction --no-progress --prefer-dist
npm ci --no-audit --no-fund

step ".env"
if [ ! -f .env ]; then
    cp .env.example .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/; s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
    php artisan key:generate --no-interaction
fi

step "Assets"
npm run build
npm run build:theme

step "Database (migrate + seed, first time only)"
if ! php artisan migrate:status --no-interaction >/dev/null 2>&1 || php artisan migrate:status --no-interaction | grep -q "Pending"; then
    php artisan migrate --force --no-interaction
    php artisan db:seed --force --no-interaction
fi
php artisan storage:link --no-interaction 2>/dev/null || true
php artisan filament:assets --no-interaction >/dev/null

step "Done"
echo "Dev server : php artisan serve   (admin: /admin — admin@university.test; password printed once by db:seed)"
echo "Tests      : php artisan test Modules/<Module>/tests   (DB university_test)"
