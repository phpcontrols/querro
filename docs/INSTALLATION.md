# Querro Installation Guide

Complete instructions for installing and configuring Querro.

## Prerequisites

### Runtime Requirements

Querro runs on a standard PHP stack:

- **PHP 8.1 or higher** with required extensions
- **MySQL/MariaDB** database server
- **Apache web server** with mod_rewrite enabled

### Installation Tools (one-time setup only)

- **Composer** for PHP dependency management
- **Node.js and npm** for installing frontend dependencies (Bootstrap, jQuery, etc.)
- **Git** for version control

**Note**: Node.js is **NOT required** to run the application. It's only needed during initial setup to install frontend libraries via `npm install`. Once installed, the app runs entirely on PHP + Apache + MySQL.

## Quick Start

```bash
# Clone repository
git clone [repository-url] querro
cd querro

# Environment variables
cp .env.local.example .env

# Edit .env with your database credentials

# Install dependencies
composer install
npm install

# Install phpGrid dependencies
cd includes/phpGrid
composer install --ignore-platform-req=ext-gd
npm install

# Go back to root folder
cd ../..

# Setup database (creates all tables automatically)
mysql -u root -p -e "CREATE DATABASE querro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p querro < install.sql

# Set permissions (if needed)
chmod -R 775 var/

# Clear cache
php bin/console cache:clear

# Start development server or configure Apache virtual host
```

## System Requirements

### PHP Requirements

**Version**: PHP 8.1 or higher

**Required Extensions**:
- `ext-ctype` - Character type checking
- `ext-iconv` - Character encoding conversion
- `mysqli` - MySQL database connectivity
- `curl` - HTTP requests (for OpenAI API)
- `json` - JSON encoding/decoding

Verify installed extensions:
```bash
php -m | grep -E 'ctype|iconv|mysqli|curl|json'
```

### Database Requirements

- **MySQL 5.7+** or **MariaDB 10.2+**
- All tables must have a primary key (required by phpGrid)
- UTF-8mb4 character set support
- IP whitelisting required for remote database connections

### Web Server Requirements

- **Apache 2.4+** with mod_rewrite enabled
- Write permissions for cache and log directories
- Support for .htaccess files

Enable mod_rewrite on Ubuntu/Debian:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Installation Tools (Required for Setup Only)

- **Composer 2.0+** - [getcomposer.org](https://getcomposer.org) - Installs PHP dependencies
- **Node.js 14+** and **npm 6+** - [nodejs.org](https://nodejs.org) - Installs frontend libraries (one-time setup)
- **Git** - [git-scm.com](https://git-scm.com) - Clone repository

**Important**: After running `npm install` during setup, Node.js is not required to run the application. The installed frontend libraries become static files served by Apache.

## Installation Steps

### 1. Clone Repository

```bash
git clone [repository-url] querro
cd querro
```

### 2. Install PHP Dependencies

Install Symfony framework, Doctrine ORM, and other PHP libraries:

```bash
composer install
```

This installs:
- Symfony 6.4 framework components
- PHP-SQL-Parser for query parsing
- Additional utility libraries

### 3. Install Frontend Dependencies (One-Time Setup)

Install Bootstrap, jQuery, and UI components as static files:

```bash
npm install
```

This installs:
- Bootstrap 3.4.1
- jQuery 3.5.1
- Bootstrap Select, jQuery UI
- ACE Editor, and other UI libraries

**Note**: These become static JavaScript/CSS files in `node_modules/`. Node.js is not required after this step - Apache serves these files directly.

### 4. Install phpGrid Dependencies

phpGrid has its own dependencies that need to be installed:

```bash
cd includes/phpGrid
composer install --ignore-platform-req=ext-gd
npm install
cd ../..
```

**Note**: The `--ignore-platform-req=ext-gd` flag is used because GD extension is not required for basic phpGrid functionality.

### 5. Create Database

Create the MySQL database with UTF-8mb4 encoding:

```bash
mysql -u root -p -e "CREATE DATABASE querro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or using MySQL client:
```sql
CREATE DATABASE querro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Configure Environment

Create local environment configuration:

```bash
cp .env .env.local
```

Edit `.env.local` with your settings:

```bash
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=querro

# Application Configuration
APP_URL=http://localhost/querro
APP_ENV=dev
APP_SECRET=generate_random_32_character_string_here
```

Generate a secure APP_SECRET:
```bash
php -r "echo bin2hex(random_bytes(16));"
```

### 7. Setup Database Schema

**Option A: SQL Import (Recommended - Faster & Simpler)**

Import the complete database schema from the provided SQL file:

```bash
# Local development
mysql -u root -proot querro < install.sql

# Or without password in command (will prompt)
mysql -u root -p querro < install.sql
```

**Option B: Doctrine Migrations (Alternative)**

If you prefer using Symfony's migration system:

```bash
php bin/console doctrine:migrations:migrate
```

**Note**: If migrations get stuck or hang, use Option A (SQL import) instead.

**Tables Created:**
- `user` - User authentication
- `account` - User accounts
- `dbs` - External database connections
- `query` - Saved queries
- `column_prop` - Column formatting preferences
- `messenger_messages` - Async messaging

### 8. Set Permissions

Ensure writable directories have correct permissions:

```bash
chmod -R 775 var/
```

### 9. Clear Cache

```bash
php bin/console cache:clear
```

## Configuration

### Environment Variables

Querro uses environment variables defined in `.env` and `.env.local`:

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database server hostname | `localhost` |
| `DB_USER` | Database username | `root` |
| `DB_PASS` | Database password | `your_password` |
| `DB_NAME` | Database name | `querro` |
| `APP_URL` | Application URL | `http://localhost/querro` |
| `APP_ENV` | Environment (dev/prod) | `dev` |
| `APP_SECRET` | Security secret | `random_32_char_string` |

### Configuration Files

**`config/config.php`** - Main configuration file that loads environment variables and defines constants.

**`config/databases.php`** - Manages database connections with session-based caching for performance.

## Web Server Configuration

### Apache Setup

Querro requires Apache with mod_rewrite enabled. The project includes an `.htaccess` file at the root.

#### Directory Structure

```
/var/www/querro/          # Project root (DocumentRoot points here)
├── public/               # Public assets
├── q/                    # Main application (bypasses Symfony)
├── resources/            # Frontend resources
├── includes/             # Core libraries (protected)
├── config/               # Configuration (protected)
└── .htaccess             # Routing configuration
```

#### .htaccess Overview

The `.htaccess` file provides:
- URL rewriting for clean URLs
- Protection for sensitive directories
- Direct access to `/q/` directory (bypasses Symfony)
- Fallback routing to `public/index.php`

```apache
RewriteEngine On

# Bypass framework for these directories
RewriteRule ^q/ - [L]
RewriteRule ^resources/ - [L]
RewriteRule ^public/ - [L]

# Serve static files directly
RewriteCond %{DOCUMENT_ROOT}/public/$1 -f
RewriteRule (.+) public/$1 [L]

# Route everything else to Symfony
RewriteRule (.*) public/index.php?route=$1 [L,QSA]
```

## Post-Installation

### 1. Create Admin User

There are two ways to create a user:

**Option A (Recommended): Using Symfony Console**
```bash
php bin/console app:create-user admin@example.com password123
```

**Option B: Directly in Database**
```sql
-- The install.sql already created account ID 1 (Default Account)
INSERT INTO user (email, roles, password, account_id, username, active)
VALUES (
    'admin@example.com',
    '["ROLE_USER"]',
    '$2y$13$hashed_password_here',
    1,
    'admin@example.com',
    1
);
```

Generate password hash:
```bash
php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
```

**Note**: The `account_id` should be `1` because `install.sql` automatically creates a default account with ID 1.

1. Read the [README](README.md) for project overview and features
2. Review the [Architecture section](README.md#architecture) to understand the hybrid design
3. Explore the query builder at `/q/query.php`
4. Add your first external database connection in Settings
5. Try the AI-powered SQL generation (if OpenAI API configured

## License

MIT License - See LICENSE file for details.
