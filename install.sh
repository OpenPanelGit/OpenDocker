#!/bin/bash

# OpenPanel Installation Script
set -e

# ANSI Color Codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting OpenPanel installation...${NC}"

# Check for Root
if [ "$(id -u)" -ne 0 ]; then
    echo -e "${YELLOW}This script suggests running as root to install system dependencies.${NC}"
    echo -e "${YELLOW}If you have not installed PHP, Composer, or Node.js yet, please run with sudo.${NC}"
    read -p "Continue anyway? (y/n) [n]: " continue_non_root
    if [[ ! "$continue_non_root" =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Function to detect OS
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    else
        echo -e "${RED}Cannot detect OS. System dependency installation might fail.${NC}"
        OS="Unknown"
    fi
}

# Install System Dependencies
install_sys_deps() {
    detect_os
    echo -e "${YELLOW}Detected OS: $OS $VER${NC}"
    
    if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
        echo "Updating package lists..."
        apt-get update -y
        
        echo "Installing basic tools (curl, git, unzip, software-properties-common)..."
        apt-get install -y curl git unzip software-properties-common
        
        # Add PHP Repository (Ondrej) if Ubuntu
        if [[ "$OS" == *"Ubuntu"* ]]; then
            echo "Adding PHP PPA..."
            add-apt-repository ppa:ondrej/php -y
            apt-get update -y
        fi
        
        echo "Installing PHP 8.2 and extensions..."
        apt-get install -y php8.2 php8.2-common php8.2-cli php8.2-gd php8.2-mysql \
                           php8.2-mbstring php8.2-bcmath php8.2-xml php8.2-fpm \
                           php8.2-curl php8.2-zip php8.2-intl php8.2-pdo sqlite3
                           
        # Install Composer
        if ! command -v composer &> /dev/null; then
            echo "Installing Composer..."
            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        fi
        
        # Install Node.js (LTS)
        if ! command -v node &> /dev/null; then
            echo "Installing Node.js..."
            curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
            apt-get install -y nodejs
        fi
        
    else
        echo -e "${YELLOW}Automatic dependency installation is only supported on Ubuntu/Debian.${NC}"
        echo -e "${YELLOW}Please ensure PHP 8.2+, Composer, and Node.js are installed manually.${NC}"
    fi
}

echo "----------------------------------------------------------------"
read -p "Do you want to attempt to install system dependencies (PHP, Node, Composer)? (y/n) [y]: " install_deps
install_deps=${install_deps:-y}

if [[ "$install_deps" =~ ^[Yy]$ ]]; then
    install_sys_deps
fi

# Check prerequisites again just in case
echo "Checking prerequisites..."
command -v php >/dev/null 2>&1 || { echo -e "${RED}PHP is required but not installed. Aborting.${NC}"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo -e "${RED}Composer is required but not installed. Aborting.${NC}"; exit 1; }
command -v npm >/dev/null 2>&1 || { echo -e "${RED}NPM/Node.js is required but not installed. Aborting.${NC}"; exit 1; }

# Install PHP dependencies
echo -e "${GREEN}Installing PHP dependencies...${NC}"
# Allow running as root for composer (warns usually)
export COMPOSER_ALLOW_SUPERUSER=1
composer install --optimize-autoloader --no-dev

# Setup Environment
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    echo "Generating application key..."
    php artisan key:generate
else
    echo ".env file already exists, skipping creation."
fi

# Function to update .env variable
update_env() {
    local key=$1
    local value=$2
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

# Database Configuration
echo "----------------------------------------------------------------"
echo -e "${YELLOW}Database Configuration${NC}"
echo "Select database type:"
echo "1) SQLite (Zero config, easiest for testing)"
echo "2) MySQL / MariaDB"
echo "3) PostgreSQL"
read -p "Enter choice [1]: " db_choice
db_choice=${db_choice:-1}

if [ "$db_choice" = "1" ]; then
    echo "Configuring SQLite..."
    touch database/database.sqlite
    update_env "DB_CONNECTION" "sqlite"
    update_env "DB_DATABASE" "database.sqlite"
    update_env "DB_HOST" ""
    update_env "DB_PORT" ""
    update_env "DB_USERNAME" ""
    update_env "DB_PASSWORD" ""

elif [ "$db_choice" = "2" ]; then
    read -p "Database Host [127.0.0.1]: " db_host
    db_host=${db_host:-127.0.0.1}
    
    read -p "Database Port [3306]: " db_port
    db_port=${db_port:-3306}
    
    read -p "Database Name [panel]: " db_name
    db_name=${db_name:-panel}
    
    read -p "Database User [openpanel]: " db_user
    db_user=${db_user:-pelican}
    
    read -s -p "Database Password: " db_pass
    echo ""
    
    update_env "DB_CONNECTION" "mysql"
    update_env "DB_HOST" "$db_host"
    update_env "DB_PORT" "$db_port"
    update_env "DB_DATABASE" "$db_name"
    update_env "DB_USERNAME" "$db_user"
    update_env "DB_PASSWORD" "$db_pass"

elif [ "$db_choice" = "3" ]; then
    read -p "Database Host [127.0.0.1]: " db_host
    db_host=${db_host:-127.0.0.1}
    
    read -p "Database Port [5432]: " db_port
    db_port=${db_port:-5432}
    
    read -p "Database Name [panel]: " db_name
    db_name=${db_name:-panel}
    
    read -p "Database User [openpanel]: " db_user
    db_user=${db_user:-pelican}
    
    read -s -p "Database Password: " db_pass
    echo ""
    
    update_env "DB_CONNECTION" "pgsql"
    update_env "DB_HOST" "$db_host"
    update_env "DB_PORT" "$db_port"
    update_env "DB_DATABASE" "$db_name"
    update_env "DB_USERNAME" "$db_user"
    update_env "DB_PASSWORD" "$db_pass"
fi

# Run Migrations
echo -e "${GREEN}Running database migrations...${NC}"
# Use yes to accept potential formatting questions if any, mostly force
php artisan migrate --seed --force

# Create Admin User
echo "----------------------------------------------------------------"
read -p "Do you want to create an Admin User now? (y/n) [y]: " create_user
create_user=${create_user:-y}

if [[ "$create_user" =~ ^[Yy]$ ]]; then
    php artisan p:user:make
fi

# Set Permissions
echo -e "${GREEN}Setting permissions...${NC}"
chmod -R 755 storage bootstrap/cache
# Ensure correct ownership if we can determine the web user (www-data)
if id "www-data" &>/dev/null; then
    chown -R www-data:www-data storage bootstrap/cache
fi

# Link Storage
echo "Linking storage..."
php artisan storage:link

# Install JS dependencies and build
echo -e "${GREEN}Installing JS dependencies and building assets...${NC}"
npm install
npm run build

echo "----------------------------------------------------------------"
echo -e "${GREEN}Installation process finished successfully!${NC}"
echo "Your application should now be ready."
