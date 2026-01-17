#!/bin/bash

# =========================================================================
#  OpenPanel - Script d'Installation Automatisé
# =========================================================================
#  Support: Ubuntu 22.04 / 24.04
# =========================================================================

set -e

# Couleurs pour la sortie
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

function log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

function warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

function error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

if [[ $EUID -ne 0 ]]; then
   error "Ce script doit être exécuté en tant que root (sudo)."
fi

log "Début de l'installation automatisée d'OpenPanel..."

# 1. Mise à jour du système
log "Mise à jour du système..."
apt update -y && apt upgrade -y

# 2. Installation des dépendances de base
log "Installation des outils de base..."
apt install -y ca-certificates curl gnupg lsb-release software-properties-common git unzip zip jq wget ufw acl

# 3. Installation de PHP 8.4
log "Configuration du dépôt PHP..."
if ! grep -q "ondrej/php" /etc/apt/sources.list.d/* 2>/dev/null; then
    add-apt-repository -y ppa:ondrej/php
    apt update -y
fi

log "Installation de PHP 8.4 et extensions..."
apt install -y php8.4 php8.4-cli php8.4-common php8.4-fpm php8.4-gd php8.4-mysql \
php8.4-mbstring php8.4-bcmath php8.4-xml php8.4-curl php8.4-zip \
php8.4-readline php8.4-redis php8.4-simplexml php8.4-dom

# 4. Installation de MariaDB et Redis
log "Installation de MariaDB et Redis..."
apt install -y mariadb-server mariadb-client redis-server
systemctl enable --now mariadb
systemctl enable --now redis-server

# 5. Configuration de la Base de Données
log "Configuration de la base de données..."
DB_NAME="openpanel"
DB_USER="openpanel"
DB_PASS=$(openssl rand -base64 12)

mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

log "Base de données créée : ${DB_NAME}"
log "Utilisateur DB : ${DB_USER}"
log "Mot de passe DB : ${DB_PASS}"

# 6. Installation de Composer
log "Installation de Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# 7. Installation de Node.js
log "Installation de Node.js..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
fi

# 8. Préparation du dossier
INSTALL_DIR=$(pwd)
log "Installation dans le dossier actuel : ${INSTALL_DIR}"

if [ ! -f "composer.json" ]; then
    error "Le fichier composer.json est introuvable. Veuillez exécuter ce script à la racine du projet OpenPanel."
fi

# 9. Installation des dépendances
log "Installation des dépendances PHP..."
composer install --no-dev --optimize-autoloader

log "Installation des dépendances JS et compilation..."
npm install
npm run build

# 10. Configuration du Panel
log "Initialisation du fichier .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Mise à jour du .env avec les infos DB
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env

php artisan key:generate --force

log "Migration de la base de données (seed inclus)..."
php artisan migrate --seed --force

# 11. Permissions
log "Configuration des permissions..."
chown -R www-data:www-data ${INSTALL_DIR}
chmod -R 755 ${INSTALL_DIR}/storage ${INSTALL_DIR}/bootstrap/cache

# 12. Service Queue Worker
log "Configuration du service de queue..."
cat > /etc/systemd/system/openpanel-worker.service <<EOF
[Unit]
Description=OpenPanel Queue Worker
After=redis-server.service
Requires=redis-server.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php ${INSTALL_DIR}/artisan queue:work --queue=high,standard,low --sleep=3 --tries=3
StartLimitBurst=30
RestartSec=5s

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now openpanel-worker

# 13. Nginx (Optionnel mais recommandé)
warn "Voulez-vous configurer Nginx automatiquement ? (y/n)"
read -r CONFIRM_NGINX
if [[ $CONFIRM_NGINX == "y" ]]; then
    log "Configuration de Nginx..."
    apt install -y nginx
    DOMAIN="localhost"
    warn "Entrez votre domaine (ex: panel.exemple.com) [localhost]:"
    read -r USER_DOMAIN
    if [[ -n $USER_DOMAIN ]]; then
        DOMAIN=$USER_DOMAIN
    fi

    cat > /etc/nginx/sites-available/openpanel.conf <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;
    index index.html index.htm index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    access_log off;
    error_log  /var/log/nginx/openpanel.app-error.log error;

    client_max_body_size 100m;
    client_body_timeout 120s;

    sendfile off;

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size=100M";
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF
    ln -sf /etc/nginx/sites-available/openpanel.conf /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default || true
    nginx -t
    systemctl restart nginx
    log "Nginx configuré pour ${DOMAIN}"
fi

log "--------------------------------------------------------"
log "INSTALLATION TERMINÉE AVEC SUCCÈS !"
log "--------------------------------------------------------"
log "Informations de Base de Données (.env mis à jour) :"
log "User: ${DB_USER}"
log "Pass: ${DB_PASS}"
log "Db: ${DB_NAME}"
log ""
log "Vous pouvez maintenant créer votre premier utilisateur :"
log "php artisan p:user:make"
log "--------------------------------------------------------"
