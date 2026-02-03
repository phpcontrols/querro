# Querro Configuration Guide

Complete guide to configuring and customizing Querro.

## Table of Contents

1. [Environment Variables](#environment-variables)
2. [Configuration Files](#configuration-files)
3. [Database Configuration](#database-configuration)
4. [OpenAI Integration](#openai-integration)
5. [Security Configuration](#security-configuration)

---

## Environment Variables

Querro uses environment variables for configuration, loaded from `.env` or `.env.local` files.

### Core Variables

**Database Connection (Application Database)**

```bash
# MySQL connection for Querro's internal database
DB_HOST=localhost          # Database server hostname
DB_USER=root              # Database username
DB_PASS=your_password     # Database password
DB_NAME=querro            # Database name
```

**Application Settings**

```bash
# Application environment (dev or prod)
APP_ENV=dev               # Use 'prod' for production

# Debug mode (0 or 1)
APP_DEBUG=1               # Set to 0 in production

# Application URL (used for links and redirects)
APP_URL=http://localhost/querro

# Security secret (random 32-character string) required by Symfony for security
APP_SECRET=your_random_32_character_secret_here
```

### Generating APP_SECRET

Generate a secure random secret:

```bash
php -r "echo bin2hex(random_bytes(16));"
```

---

## Configuration Files

### Main Configuration: `config/config.php`

The central configuration file that defines application constants.

**Key Constants:**

```php
<?php

// Database Configuration (loaded from .env)
define('APP_DBHOST', getenv('DB_HOST') ?: 'localhost');
define('APP_DBUSER', getenv('DB_USER') ?: 'root');
define('APP_DBPASS', getenv('DB_PASS') ?: 'your_database_password');
define('APP_DBNAME', getenv('DB_NAME') ?: 'querro');
define('APP_URL', getenv('APP_URL') ?: 'https://app.querro-dev.local');
```

## Database Configuration

### Application Database Schema

The Querro application database contains these tables:

**Core Tables:**

- `account` - User accounts/organizations
- `user` - User authentication and profiles
- `dbs` - External database connections
- `query` - Saved queries
- `column_prop` - Column formatting preferences (Not used. Reserved for future use)

### Database Requirements

Databases added in Settings must meet these requirements:

**MySQL Version:**
- MySQL 5.7+ or MariaDB 10.2+

**Table Requirements:**
- All tables MUST have a primary key (required by phpGrid)

**User Permissions:**

Minimum permissions needed:
```sql
-- Read-only access (recommended)
GRANT SELECT ON database_name.* TO 'querro_user'@'%';

-- Full access (if inline editing needed)
GRANT SELECT, INSERT, UPDATE, DELETE ON database_name.* TO 'querro_user'@'%';
```

## OpenAI Integration

### API Key Setup

**1. Obtain API Key:**
- Sign up at [platform.openai.com](https://platform.openai.com)
- Navigate to API Keys section
- Generate a new secret key

**2. Configure Querro:**

Visit "settings.php" n browser, login, and enter API key in the form.

**3. Verify Configuration:**

The API key is encrypted and stored in the database when first used.

### Custom Prompts

Customize AI behavior by editing the system prompt in `q/query-ai.php`:

```php
$systemPrompt = "You are a MySQL expert. Convert natural language to SQL queries.
Rules:
- Use the provided database schema
- Always use proper JOIN syntax
- Avoid using ORDER BY and LIMIT (handled by application)
- Return only the SQL query, no explanations";
```

---

## Security Configuration

### Password Hashing

Querro uses bcrypt password hashing via Symfony Security.

**Configuration:** `config/packages/security.yaml`

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: auto
            cost: 12  # Increase for stronger hashing (slower)
```

**Cost Values:**
- `10` - Fast, less secure
- `12` - Balanced (default)
- `13-14` - Strong, slower

### phpGrid Configuration

**Global Grid Settings:**

Edit `includes/phpGrid/conf.php`:

```php
define('PHPGRID_DB_HOSTNAME',$dbSettings['server']); // database host name
define('PHPGRID_DB_USERNAME',$dbSettings['username']); // database user name
define('PHPGRID_DB_PASSWORD',$dbSettings['password']); // database password
define('PHPGRID_DB_NAME',    $dbSettings['name']); // database name
define('PHPGRID_DB_TYPE', 'mysql');  // database type
define('PHPGRID_DB_CHARSET','utf8');
```

---

*For more help, see [INSTALLATION.md](INSTALLATION.md) and [FAQ.md](FAQ.md)*
