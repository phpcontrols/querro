# Querro - Frequently Asked Questions

Quick answers to common questions about Querro.

## Table of Contents

- [General Questions](#general-questions)
- [Installation & Setup](#installation--setup)
- [Using Querro](#using-querro)
- [AI Features](#ai-features)
- [Troubleshooting](#troubleshooting)
- [Security](#security)

---

## General Questions

### What is Querro?

Querro is a web-based MySQL database query tool with AI-powered text-to-SQL conversion. It lets you connect to multiple databases, write queries with syntax highlighting, and view results in interactive data grids.

### Is Querro free?

Yes! Querro is completely free and open source under the MIT license. You can use it, modify it, and deploy it without any licensing fees.

### Does Querro require Node.js to run?

**No.** Node.js is only needed during initial setup to install frontend libraries (Bootstrap, jQuery, etc.). After running `npm install`, the app runs entirely on PHP + Apache + MySQL. No Node.js process is required at runtime.

### What databases does Querro support?

Currently **MySQL only** (MySQL 5.7+ and MariaDB 10.2+). PostgreSQL, SQL Server, and Oracle support were removed to simplify the codebase.

### Why doesn't Querro use React/Vue/Angular?

Querro deliberately uses a classic server-side stack (PHP, jQuery, Bootstrap) because:
- Database query tools don't need complex frontend frameworks
- Server-side rendering is simpler and faster for this use case
- Lower barrier to entry for developers
- Less complexity, fewer dependencies, easier maintenance

---

## Installation & Setup

### How do I install Querro?

See the complete [Installation Guide](INSTALLATION.md). Quick summary:

```bash
# Clone repository
git clone [repository-url] querro
cd querro

# Install dependencies
composer install
npm install

# Install phpGrid dependencies
cd includes/phpGrid
composer install --ignore-platform-req=ext-gd
npm install
cd ../..

# Setup database
mysql -u root -p -e "CREATE DATABASE querro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p querro < install.sql

# Configure
cp .env .env.local
# Edit .env.local with your database credentials

# Clear cache
php bin/console cache:clear
```

### Do I need to configure Symfony?

No. Querro uses Symfony minimally (only for authentication). Most configuration is done via environment variables in `.env.local`:

```bash
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=querro
APP_URL=http://querro
```

### How do I create the first admin user?

**Option A (Recommended):**
```bash
php bin/console app:create-user admin@example.com password123
```

**Option B (Direct SQL):**
```sql
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

### Can I install Querro without Composer/npm?

Not recommended. Composer and npm install required dependencies:
- Composer: Symfony components, Doctrine ORM, PHP libraries
- npm: Bootstrap, jQuery, ACE Editor, UI libraries


---

## Using Querro

### How do I add a database connection?

1. Go to **Settings** (`/q/settings.php`)
2. Click **"Add New Database"**
3. Fill in connection details (host, database name, username, password)
4. Click **"Test Connection"**
5. If successful, click **"Save"**

### Why does it say "All tables must have a primary key"?

It's a database best practice for  every table a primary key. phpGrid (the data grid library) also requires all tables to have a primary key for editing, pagination, and row identification. 


### How do I save a query for later?

1. Write your SQL query in the editor
2. Click **"Save Query"** button
3. Enter a descriptive name
4. Click **"Save"**

To load: Select from the "Saved Queries" dropdown.

---

## AI Features

### How much does the AI feature cost?

The AI feature uses OpenAI's API which charges per token:

| Model | Avg. Cost Per Query |
|-------|---------------------|
| GPT-4o | $0.005 - $0.015 |
| GPT-4-turbo | $0.010 - $0.030 |
| GPT-3.5-turbo | $0.001 - $0.003 |

Most queries use 500-1000 tokens. You can monitor usage in the OpenAI dashboard.

### Why is the AI generating incorrect SQL?

Common reasons:
1. **Ambiguous description** - Be more specific about what you want
2. **Unfamiliar table names** - The AI doesn't know your exact schema
3. **Complex business logic** - May require manual SQL editing

**Tips:**
- Mention specific table and column names
- Be explicit about JOIN conditions
- Review and edit the generated SQL before running

### Does the AI have access to my database data?

**No.** The AI only receives:
- Your natural language query description
- The database schema (table/column names)

It does NOT receive:
- Actual data from your tables
- Query results
- Database credentials

---

## Troubleshooting

### I get "Database connection failed" error

**Check these:**

1. **Verify MySQL is running:**
   ```bash
   sudo systemctl status mysql
   ```

2. **Test connection manually:**
   ```bash
   mysql -h localhost -u root -p -e "SHOW DATABASES;"
   ```

3. **Check credentials in `.env.local`:**
   ```bash
   cat .env.local | grep DB_
   ```

4. **Verify database exists:**
   ```bash
   mysql -u root -p -e "SHOW DATABASES LIKE 'querro';"
   ```

### Login page shows "Internal Server Error"

**Common causes:**

1. **Missing .env.local file:**
   ```bash
   cp .env .env.local
   ```

2. **Incorrect permissions:**
   ```bash
   chmod -R 775 var/
   sudo chown -R www-data:www-data var/
   ```

3. **Check Apache error log:**
   ```bash
   tail -f /var/log/apache2/error.log
   ```

### I see "Class not found" errors

**Fix:**

1. **Regenerate autoloader:**
   ```bash
   composer dump-autoload
   ```

2. **Clear Symfony cache:**
   ```bash
   php bin/console cache:clear
   ```

3. **Reinstall dependencies:**
   ```bash
   rm -rf vendor/
   composer install
   ```


## Security

### Is it safe to store database credentials in Querro?

It's recommended to keep Querro on internal network (not public internet) only. Database passwords are stored **in plain text** in the `dbs` table (required for connections).

**Security recommendations:**
1. Use database-specific read-only users
2. Restrict network access to MySQL servers (IP whitelist)
3. Use SSL for MySQL connections
4. Limit Querro access to authorized users only
5. Keep Querro on internal network (not public internet)

### Should I enable HTTPS?

**Yes, always in production** even it's used internally only. HTTPS encrypts all traffic including login credentials and query data.

**Enable HTTPS:**
1. Obtain SSL certificate (Let's Encrypt, paid certificate, etc.)
2. Configure Apache for HTTPS (see [INSTALLATION.md](INSTALLATION.md#web-server-configuration))
3. Force redirect HTTP → HTTPS

### Can users see each other's queries?

No. Saved queries are user-specific (filtered by `user_id`). Users can only see their own saved queries.

### What user permissions does Querro need?

**Querro Application Database:**
- Full permissions (SELECT, INSERT, UPDATE, DELETE) on `querro` database

**External Databases:**
- **Read-only**: `SELECT` permission only (recommended)
- **Full access**: `SELECT, INSERT, UPDATE, DELETE` (if inline editing needed)

**Example:**
```sql
-- Read-only user (recommended)
CREATE USER 'querro_readonly'@'%' IDENTIFIED BY 'password';
GRANT SELECT ON database_name.* TO 'querro_readonly'@'%';

-- Full access user
CREATE USER 'querro_full'@'%' IDENTIFIED BY 'password';
GRANT SELECT, INSERT, UPDATE, DELETE ON database_name.* TO 'querro_full'@'%';
```
