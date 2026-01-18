# OpenPanel Installation Guide

This guide explains how to use the automated `install.sh` script to set up OpenPanel.

## Prerequisites

Before running the installation script, ensure your system has the following installed:

- **PHP** (8.1 or higher)
- **Composer** (Dependency Manager for PHP)
- **Node.js & NPM** (For building frontend assets)
- **Database** (Optional if using SQLite; otherwise MySQL/MariaDB or PostgreSQL)

## Automated Installation

The script can now **automatically install system dependencies** (PHP, Composer, Node.js) on Ubuntu/Debian systems.

1. **Make the script executable**  
   Open your terminal in the project directory and run:
   ```bash
   chmod +x install.sh
   ```

2. **Run the installer**  
   If you need to install system dependencies (PHP, etc.), run as **root** or with **sudo**:
   ```bash
   sudo ./install.sh
   ```
   
   The script will guide you through the process:
   - **System Check**: Detects if you need to install PHP, Node.js, or Composer.
   - **Environment Setup**: Automatically creates `.env` and generates keys.
   - **Database Configuration**: Interactive setup for SQLite, MySQL, or PostgreSQL.
   - **Migrations**: Database schema setup.
   - **Admin User**: Create an administrator account.
   - **Build**: Compiles frontend assets.

3. **Start the Server**
   Once the script finishes, you can start the development server:
   ```bash
   php artisan serve
   ```
   Access the panel at the URL shown (usually `http://127.0.0.1:8000`).

## Troubleshooting

- **Permissions**: If `chmod` fails, try running with `sudo` (Linux/macOS) or check your folder permissions.
- **Database Errors**: If migrations fail, double-check the credentials you entered. You can edit the `.env` file manually and run `php artisan migrate` to retry.
