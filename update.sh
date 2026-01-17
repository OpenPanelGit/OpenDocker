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

if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}[ERROR]${NC} Ce script doit être exécuté en tant que root (sudo ./update.sh)."
    exit 1
fi

# Marquer le dossier comme sûr pour Git
git config --global --add safe.directory $(pwd)

log "Début de la mise à jour d'OpenPanel..."

# 1. Récupérer les derniers changements
log "Vérification de l'état Git..."
git config --global --add safe.directory $(pwd)
git stash || true
if ! git pull origin main; then
    echo -e "${RED}[ERROR]${NC} Le git pull a échoué. Vérifiez les conflits."
    exit 1
fi

# 2. Détecter le gestionnaire de paquets
if command -v pnpm &> /dev/null; then
    PKG_MGR="pnpm"
elif command -v npm &> /dev/null; then
    PKG_MGR="npm"
else
    echo -e "${RED}[ERROR]${NC} Aucun gestionnaire de paquets (npm/pnpm) trouvé."
    exit 1
fi

log "Utilisation de $PKG_MGR pour l'installation..."

# 3. Installation des dépendances PHP
log "Mise à jour Composer..."
composer install --no-dev --optimize-autoloader

# 4. Installation JS et Build
log "Installation des dépendances JS..."
$PKG_MGR install
log "Lancement du build (Reconstruction de l'interface)..."
$PKG_MGR run build

# 5. Base de données et Cache
log "Migration et nettoyage..."
php artisan migrate --force
php artisan view:clear && php artisan config:clear && php artisan cache:clear && php artisan route:clear

# 6. Permissions
log "Réapplication des droits www-data..."
chown -R www-data:www-data .
chmod -R 755 storage bootstrap/cache

log "✅ Mise à jour terminée ! Version actuelle : $(git rev-parse --short HEAD)"
