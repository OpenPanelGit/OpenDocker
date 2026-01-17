# 🛠️ Guide d'Installation d'OpenDocker

Ce guide décrit les étapes pour installer **OpenDocker** (basé sur Pyrodactyl) sur une machine virtuelle (Ubuntu 22.04/24.04 recommandé).

## 1. Préparer le système et installer les dépendances

```bash
# Mettre à jour le système
apt update -y && apt upgrade -y

# Installer les outils de base
apt install -y ca-certificates curl gnupg lsb-release software-properties-common git unzip zip jq wget ufw acl

# Ajouter le dépôt PHP (Ondrej) pour PHP 8.4
add-apt-repository -y ppa:ondrej/php
apt update -y

# Installer PHP 8.4 et les extensions nécessaires
apt install -y php8.4 php8.4-cli php8.4-common php8.4-fpm php8.4-gd php8.4-mysql \
php8.4-mbstring php8.4-bcmath php8.4-xml php8.4-curl php8.4-zip \
php8.4-readline php8.4-redis php8.4-simplexml php8.4-dom

# Installer MariaDB (Base de données) et Redis (Cache)
apt install -y mariadb-server mariadb-client redis-server

# Installer Composer (Gestionnaire de paquets PHP)
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer Node.js (pour compiler les assets du panel)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

## 2. Télécharger OpenDocker

```bash
# Créer le dossier d'installation
mkdir -p /var/www/opendocker
cd /var/www/opendocker

# Cloner le dépôt
git clone https://github.com/OpenPanelGit/OpenDocker.git .

# Copier le fichier d'environnement
cp .env.example .env

# Installer les dépendances PHP
composer install --no-dev --optimize-autoloader

# Installer les dépendances JavaScript et compiler (Build production)
npm install
npm run build
```

## 3. Configuration de la Base de Données

Connectez-vous à MySQL/MariaDB :

```bash
mysql -u root -p
```

Exécutez les requêtes SQL suivantes :

```sql
-- Remplacez 'votre_mot_de_passe' par un mot de passe sécurisé
CREATE USER 'opendocker'@'127.0.0.1' IDENTIFIED BY 'votre_mot_de_passe';
CREATE DATABASE panel;
GRANT ALL PRIVILEGES ON panel.* TO 'opendocker'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

## 4. Configuration du Panel

```bash
# Générer la clé de chiffrement
php artisan key:generate --force

# Configurer l'environnement (Database host, redis, etc.)
php artisan p:environment:setup

# Configurer la base de données interne
php artisan p:environment:database

# Migrer la base de données
php artisan migrate --seed --force

# Créer votre compte administrateur
php artisan p:user:make
```

## 5. Permissions et Droits

```bash
# Donner la propriété à www-data (utilisateur web)
chown -R www-data:www-data /var/www/opendocker
chmod -R 755 /var/www/opendocker/storage /var/www/opendocker/bootstrap/cache
```

## 6. Configurer le Queue Worker (Tâches de fond)

Créez le fichier de service `/etc/systemd/system/pteroq.service` :

```ini
[Unit]
Description=OpenDocker Queue Worker
After=redis-server.service
Requires=redis-server.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/opendocker/artisan queue:work --queue=high,standard,low --sleep=3 --tries=3
StartLimitBurst=30
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

Activez le service :

```bash
systemctl enable --now pteroq
```

## 7. Configurer le Serveur Web (Nginx)

Installez Nginx :

```bash
apt install -y nginx
```

Créez la configuration `/etc/nginx/sites-available/opendocker.conf` (remplacez `votre-domaine.com`) :

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/opendocker/public;
    index index.html index.htm index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    access_log off;
    error_log  /var/log/nginx/opendocker.app-error.log error;

    client_max_body_size 100m;
    client_body_timeout 120s;

    sendfile off;

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size=100M";
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
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
```

Activez le site :

```bash
ln -s /etc/nginx/sites-available/opendocker.conf /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```
