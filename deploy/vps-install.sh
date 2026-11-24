#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# FAS Lombok 2026 - Zero-Disruption VPS Deploy Script
# Target Domain : faslombok.galipatsistem.com
# SSL Mode      : Cloudflare Flexible SSL (Origin HTTP port 80, NO HTTP->HTTPS redirect)
# Multi-project : Safe alongside other existing websites/PHP versions on the VPS
# ==============================================================================

DOMAIN="${DOMAIN:-faslombok.galipatsistem.com}"
APP_DIR="${APP_DIR:-/var/www/faslombok}"
APP_URL="https://${DOMAIN}"

echo "============================================================"
echo " Deploying FAS Lombok 2026 -> ${APP_URL}"
echo " Directory : ${APP_DIR}"
echo "============================================================"

if [ "$(id -u)" -ne 0 ]; then
    echo "[ERROR] Jalankan script ini dengan sudo / root."
    exit 1
fi

cd "$APP_DIR"

# ------------------------------------------------------------------------------
# 1. Detect PHP >= 8.3 without changing global default PHP for other projects
# ------------------------------------------------------------------------------
find_php83_plus() {
    for bin in php8.5 php8.4 php8.3 php /www/server/php/85/bin/php /www/server/php/84/bin/php /www/server/php/83/bin/php; do
        if command -v "$bin" >/dev/null 2>&1 || [ -x "$bin" ]; then
            ver="$("$bin" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "0.0")"
            major="${ver%%.*}"
            minor="${ver##*.}"
            if [ "${major:-0}" -gt 8 ] || { [ "${major:-0}" -eq 8 ] && [ "${minor:-0}" -ge 3 ]; }; then
                command -v "$bin" || echo "$bin"
                return 0
            fi
        fi
    done
    return 1
}

PHP_BIN="$(find_php83_plus || true)"

if [ -z "$PHP_BIN" ]; then
    echo "[1/6] PHP >= 8.3 belum ditemukan. Menginstal PHP 8.3 secara terisolasi (tanpa mengubah PHP default proyek lain)..."
    if command -v apt-get >/dev/null 2>&1; then
        apt-get update -y
        apt-get install -y software-properties-common ca-certificates lsb-release curl unzip git sqlite3
        if [ -f /etc/os-release ] && grep -qi ubuntu /etc/os-release; then
            add-apt-repository -y ppa:ondrej/php
        else
            curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/trusted.gpg.d/sury-php.gpg
            echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
        fi
        apt-get update -y
        apt-get install -y php8.3-cli php8.3-fpm php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath
        PHP_BIN="$(command -v php8.3)"
    else
        echo "[ERROR] OS tidak mendukung apt-get otomatis. Silakan install PHP >= 8.3 (sqlite3, mbstring, xml, curl, zip, gd, fpm) lalu jalankan ulang."
        exit 1
    fi
fi

PHP_VER="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
echo "[OK] Menggunakan PHP ${PHP_VER} (${PHP_BIN})"

# Ensure `php` resolves to selected PHP >= 8.3 inside this script ONLY (leaves global PHP untouched)
TMP_SHIM_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_SHIM_DIR"' EXIT
ln -sf "$PHP_BIN" "${TMP_SHIM_DIR}/php"
export PATH="${TMP_SHIM_DIR}:${PATH}"

# Ensure required PHP extensions for the selected PHP version on apt-based systems
if command -v apt-get >/dev/null 2>&1 && [[ "$PHP_BIN" == /usr/bin/php* ]]; then
    MISSING_PKGS=()
    for ext_check in "pdo_sqlite:sqlite3" "mbstring:mbstring" "xml:xml" "curl:curl" "zip:zip" "gd:gd" "bcmath:bcmath"; do
        mod="${ext_check%%:*}"
        pkg_suffix="${ext_check##*:}"
        if ! "$PHP_BIN" -m | grep -qi "^${mod}$"; then
            MISSING_PKGS+=("php${PHP_VER}-${pkg_suffix}")
        fi
    done
    if ! [ -e "/run/php/php${PHP_VER}-fpm.sock" ] && ! dpkg -s "php${PHP_VER}-fpm" >/dev/null 2>&1; then
        MISSING_PKGS+=("php${PHP_VER}-fpm")
    fi
    if [ "${#MISSING_PKGS[@]}" -gt 0 ]; then
        echo "[INFO] Menginstal ekstensi PHP ${PHP_VER}: ${MISSING_PKGS[*]}"
        apt-get update -y
        apt-get install -y "${MISSING_PKGS[@]}"
    fi
    systemctl enable --now "php${PHP_VER}-fpm" >/dev/null 2>&1 || service "php${PHP_VER}-fpm" start >/dev/null 2>&1 || true
fi

# ------------------------------------------------------------------------------
# 2. Ensure Composer & Node.js (npm)
# ------------------------------------------------------------------------------
COMPOSER_BIN="$(command -v composer || true)"
if [ -z "$COMPOSER_BIN" ]; then
    echo "[2/6] Menginstal Composer..."
    curl -sS https://getcomposer.org/installer | "$PHP_BIN" -- --install-dir=/usr/local/bin --filename=composer
    COMPOSER_BIN="/usr/local/bin/composer"
fi

if [ -x /opt/faslombok-node/bin/node ] && [ -x /opt/faslombok-node/bin/npm ]; then
    export PATH="/opt/faslombok-node/bin:${PATH}"
fi

node_meets_vite_req() {
    command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1 || return 1
    local ver major minor
    ver="$(node -v 2>/dev/null | sed 's/^v//')"
    major="${ver%%.*}"
    minor="$(echo "$ver" | cut -d. -f2)"
    if [ "${major:-0}" -ge 22 ]; then
        return 0
    elif [ "${major:-0}" -eq 20 ] && [ "${minor:-0}" -ge 19 ]; then
        return 0
    fi
    return 1
}

if ! node_meets_vite_req; then
    echo "[2/6] Menginstal Node.js 22 LTS terisolasi di /opt/faslombok-node (aman bagi proyek lain)..."
    case "$(uname -m)" in
        x86_64|amd64) NODE_ARCH="x64" ;;
        aarch64|arm64) NODE_ARCH="arm64" ;;
        *)
            echo "[ERROR] Arsitektur $(uname -m) tidak didukung otomatis untuk Node.js biner. Silakan install Node >= 20.19 atau >= 22.12."
            exit 1
            ;;
    esac
    NODE_TAR="$(curl -fsSL https://nodejs.org/dist/latest-v22.x/SHASUMS256.txt | awk -v suffix="-linux-${NODE_ARCH}.tar.gz" 'index($2, suffix) && substr($2, length($2)-length(suffix)+1) == suffix {print $2; exit}')"
    if [ -z "$NODE_TAR" ]; then
        echo "[ERROR] Gagal menentukan paket Node.js 22 dari nodejs.org."
        exit 1
    fi
    rm -rf /opt/faslombok-node
    mkdir -p /opt/faslombok-node
    curl -fsSL "https://nodejs.org/dist/latest-v22.x/${NODE_TAR}" | tar -xz --strip-components=1 -C /opt/faslombok-node
    export PATH="/opt/faslombok-node/bin:${PATH}"
fi
echo "[OK] Menggunakan Node $(node -v) ($(command -v node))"

# ------------------------------------------------------------------------------
# 3. Configure .env safely (preserves existing APP_KEY/settings on update)
# ------------------------------------------------------------------------------
echo "[3/6] Menyiapkan direktori & konfigurasi .env..."
mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache public/uploads/certificates

if [ ! -f .env ]; then
    cp .env.example .env
fi

set_env() {
    local key="$1"
    local val="$2"
    if grep -qE "^#?\s*${key}=" .env; then
        sed -i -E "s|^#?\s*${key}=.*|${key}=${val}|" .env
    else
        echo "${key}=${val}" >> .env
    fi
}

set_env "APP_NAME" "\"FAS Lombok 2026\""
set_env "APP_ENV" "production"
set_env "APP_DEBUG" "false"
set_env "APP_URL" "${APP_URL}"
set_env "ASSET_URL" "${APP_URL}"
set_env "DB_CONNECTION" "sqlite"
set_env "SESSION_DRIVER" "database"
set_env "SESSION_SECURE_COOKIE" "false"

# ------------------------------------------------------------------------------
# 4. Install Dependencies, Generate APP_KEY & Build Frontend Assets
# ------------------------------------------------------------------------------
echo "[4/6] Menginstal dependensi PHP, APP_KEY & membangun aset Vite..."
git config --global --add safe.directory "$APP_DIR" >/dev/null 2>&1 || true
if head -n 1 "$COMPOSER_BIN" 2>/dev/null | grep -qiE "sh$|bash$"; then
    COMPOSER_ALLOW_SUPERUSER=1 "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction
else
    COMPOSER_ALLOW_SUPERUSER=1 "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction
fi

if grep -qE "^APP_KEY=$" .env || ! grep -qE "^APP_KEY=base64:" .env; then
    "$PHP_BIN" artisan key:generate --force
fi

npm ci --no-audit --no-fund || npm install --no-audit --no-fund
npm run build

# ------------------------------------------------------------------------------
# 5. Prepare SQLite Database, Seed Official 2026 Data (if empty) & Permissions
# ------------------------------------------------------------------------------
echo "[5/6] Menyiapkan database SQLite & hak akses direktori..."
mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache public/uploads/certificates
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

"$PHP_BIN" artisan migrate --force

COMP_COUNT="$("$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo App\Models\Competition::count();
' 2>/dev/null || echo "0")"

if [ "${COMP_COUNT:-0}" -eq 0 ]; then
    echo "[INFO] Mengisi dataset resmi FAS Lombok 2026 (14 Lomba, 42 Juri, 208 Peserta)..."
    "$PHP_BIN" artisan db:seed --force
else
    echo "[INFO] Database sudah berisi ${COMP_COUNT} cabang lomba (skip seeding agar data live aman)."
fi

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

# Detect web server user (www-data / www / nginx)
WEB_USER="www-data"
if id www >/dev/null 2>&1 && pgrep -f "/www/server/nginx" >/dev/null 2>&1; then
    WEB_USER="www"
elif ! id www-data >/dev/null 2>&1 && id nginx >/dev/null 2>&1; then
    WEB_USER="nginx"
fi

chown -R "${WEB_USER}:${WEB_USER}" "$APP_DIR/database" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/public/uploads"
chmod -R 775 "$APP_DIR/database" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/public/uploads"

# ------------------------------------------------------------------------------
# 6. Configure Isolated Web Server VHost (HTTP Port 80 for Cloudflare Flexible)
# ------------------------------------------------------------------------------
echo "[6/6] Mengonfigurasi virtual host untuk ${DOMAIN} (HTTP Port 80 / Cloudflare Flexible)..."

# Find active PHP-FPM socket for PHP >= 8.3
FPM_SOCK=""
for sock in \
    "/run/php/php${PHP_VER}-fpm.sock" \
    "/var/run/php/php${PHP_VER}-fpm.sock" \
    "/tmp/php-cgi-$(echo "$PHP_VER" | tr -d '.').sock" \
    /run/php/php8.*-fpm.sock; do
    if [ -S "$sock" ]; then
        FPM_SOCK="unix:${sock}"
        break
    fi
done

if [ -z "$FPM_SOCK" ]; then
    # Check TCP FPM fallback
    if ss -tln 2>/dev/null | grep -q ":9000"; then
        FPM_SOCK="127.0.0.1:9000"
    fi
fi

# Case A: aaPanel Nginx
if [ -d /www/server/panel/vhost/nginx ] && [ -x /www/server/nginx/sbin/nginx ]; then
    CONF_PATH="/www/server/panel/vhost/nginx/${DOMAIN}.conf"
    cat > "$CONF_PATH" <<NGINXCONF
server {
    listen 80;
    server_name ${DOMAIN};
    index index.php index.html;
    root ${APP_DIR}/public;

    client_max_body_size 25M;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass ${FPM_SOCK:-unix:/tmp/php-cgi-83.sock};
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /www/wwwlogs/${DOMAIN}.log;
    error_log  /www/wwwlogs/${DOMAIN}.error.log;
}
NGINXCONF
    /www/server/nginx/sbin/nginx -t && /www/server/nginx/sbin/nginx -s reload

# Case B: Standard Nginx (Ubuntu/Debian sites-available or conf.d)
elif command -v nginx >/dev/null 2>&1; then
    if [ -z "$FPM_SOCK" ]; then
        echo "[ERROR] Socket PHP-FPM untuk PHP ${PHP_VER} tidak ditemukan di /run/php/."
        exit 1
    fi

    if [ -d /etc/nginx/sites-available ] && [ -d /etc/nginx/sites-enabled ]; then
        CONF_PATH="/etc/nginx/sites-available/${DOMAIN}"
        LINK_PATH="/etc/nginx/sites-enabled/${DOMAIN}"
    else
        CONF_PATH="/etc/nginx/conf.d/${DOMAIN}.conf"
        LINK_PATH=""
    fi

    cat > "$CONF_PATH" <<NGINXCONF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 25M;
    charset utf-8;

    # Origin HTTP port 80 untuk Cloudflare SSL Mode: Flexible (jangan redirect ke HTTPS di sini)
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass ${FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXCONF

    if [ -n "$LINK_PATH" ]; then
        ln -sf "$CONF_PATH" "$LINK_PATH"
    fi

    if nginx -t; then
        systemctl reload nginx || service nginx reload
    else
        echo "[ERROR] Konfigurasi Nginx gagal validasi! Menghapus vhost agar proyek lain tetap aman."
        rm -f "$CONF_PATH" ${LINK_PATH:+"$LINK_PATH"}
        exit 1
    fi

# Case C: Apache2
elif command -v apache2ctl >/dev/null 2>&1; then
    a2enmod rewrite proxy proxy_fcgi setenvif >/dev/null 2>&1 || true
    CONF_PATH="/etc/apache2/sites-available/${DOMAIN}.conf"
    cat > "$CONF_PATH" <<APACHECONF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:${FPM_SOCK}|fcgi://localhost"
    </FilesMatch>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
APACHECONF
    a2ensite "${DOMAIN}.conf"
    if apache2ctl configtest; then
        systemctl reload apache2 || service apache2 reload
    else
        a2dissite "${DOMAIN}.conf" || true
        rm -f "$CONF_PATH"
        exit 1
    fi
else
    echo "[WARN] Nginx/Apache tidak terdeteksi otomatis. Aplikasi siap di ${APP_DIR}/public, silakan arahkan webserver Anda ke folder tersebut (Port 80 HTTP)."
fi

echo "============================================================"
echo " SELESAI! Buka: https://${DOMAIN}"
echo " Login Master : 081234567890 / password123"
echo " Login Juri   : Nomor HP juri sebagai Username & Password"
echo "============================================================"
