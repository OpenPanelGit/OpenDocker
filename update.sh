#!/bin/bash

# =========================================================================
#  OpenPanel - Script de Mise à Jour Automatique
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

log "Début de la mise à jour d'OpenPanel..."

# 1. Récupérer les derniers changements (Optionnel : décommentez si vous utilisez Git)
# log "Récupération des fichiers depuis le dépôt..."
# git pull

# 2. Installation des dépendances PHP
log "Mise à jour des dépendances PHP (Composer)..."
composer install --no-dev --optimize-autoloader

# 3. Installation des dépendances JS et Compilation
log "Mise à jour des dépendances JS et Reconstruction..."
npm install
npm run build

# 4. Migration de la base de données
log "Mise à jour de la base de données..."
php artisan migrate --force

# 5. Nettoyage du cache
log "Nettoyage du cache système..."
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 6. Permissions (S'assurer que www-data peut toujours écrire)
log "Réapplication des permissions..."
chown -R www-data:www-data .
chmod -R 755 storage bootstrap/cache

log "--------------------------------------------------------"
log "MISE À JOUR TERMINÉE AVEC SUCCÈS !"
log "--------------------------------------------------------"
