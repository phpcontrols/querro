# Querro - AI Powered Database Query

Website: [querro.io](https://querro.io)

Demo: [app.querro.io](https://app.querro.io) (login: admin@example.com | password: admin)

A web-based internal database management tool that allows users to connect and query databases using AI text-to-sql. Querro takes a pragmatic approach to web development, using proven server-side technologies to deliver reliable database management without unnecessary complexity.

> [!NOTE]
> Note web-based installation is now available. No command line required. See Installation section.

## Core Features

### 1. AI-Powered SQL Generation
Natural language to SQL conversion powered by OpenAI:
- Convert natural language queries to SQL statements
- Database schema-aware query generation
- Support for multiple OpenAI models (GPT-4o, etc.)

### 2. Database Connectivity
- Connect to multiple external databases (stored in the `dbs` table)
- Currently supports MySQL only

### 3. Query Interface
Located in `q/query.php`, this provides:
- ACE Editor for writing custom SQL queries with syntax highlighting
- Save and manage reusable queries
- SQL autocomplete functionality

### 3. Interactive Data Grid Display
Powered by phpGrid, featuring:
- Interactive data tables with sorting, filtering, and searching
- Column customization
- Column properties and formatting options

## Tech Stack

### Backend
- **PHP**: 8.1
- **Framework**: Symfony 6.4
- **Database**: MySQL/MySQLi with PDO
- **Libraries**:
  - phpGrid (data grid component)

### Frontend
- **CSS Framework**: Bootstrap
- **JavaScript**:
  - jQuery
- **UI Components**:
  - Bootstrap Select
  - jQuery UI
- **Editors**:
  - ACE Editor (SQL editing)

### Third-party Services
- OpenAI API (natural language to SQL)

## Architecture

Querro only **use Symfony minimally where it adds value, remain frameworkless everywhere else.** While Symfony appears in the dependencies, Querro uses it sparingly—primarily as an authentication layer, not as a full-stack framework. 

### The `/q` Folder: Outside the Framework

The heart of Querro lives in the `/q` directory, which operates **completely independently** from Symfony. Requests to `/q/*` bypass Symfony's routing entirely (configured in `.htaccess`) and are served as direct PHP files. No controllers, no kernel bootstrap, no framework overhead.

This architectural choice means:
- The query builder (`q/query.php`) loads instantly without framework initialization
- AJAX endpoints (`q/actions.php`, `q/query-*.php`) respond with minimal overhead
- Developers work with straightforward PHP files, not framework abstractions
- Debugging follows direct code paths instead of framework internals

No framework to learn. No conventions to memorize. Just plain PHP doing exactly what you tell it to do.

### phpGrid Integration

All data grid functionality—displaying query results, pagination, sorting, filtering, inline editing, and exports—is handled by the phpGrid library. When a user runs a query, phpGrid takes over completely: rendering the grid interface, managing AJAX requests for data.

## Project Structure

```
/
├── q/                      # Main application pages
│   ├── query.php          # Query builder and execution
│   ├── preview.php        # Data visualization and charts
│   └── settings.php       # Database connection settings
|   ...
|
├── src/                   # Symfony application code
├── includes/              # Core libraries
│   ├── phpGrid/          # phpGrid library
│   ├── Database.php      # PDO-based MySQL database wrapper
│   ├── MySQLSchemaAdapter.php  # MySQL schema introspection
│   ├── DatabaseSchemaInterface.php  # Schema adapter interface
│   ├── core_db.php       # Core database functions
│   └── functions.php     # Helper functions
├── resources/            # Frontend resources
│   ├── vendor/           # Third-party UI libraries
│   │   ├── ace/         # ACE code editor
│   │   ├── select2/     # Select2 dropdown enhancement
│   │   ├── jgrowl/      # Notification library
│   │   └── bootstrap-editable/  # Inline editing
│   ├── js/              # JavaScript files
│   └── css/             # Stylesheets
├── config/               # Configuration files
│   ├── config.php        # Main configuration
│   └── databases.php     # Database connections
├── public/               # Public assets
├── templates/            # Twig templates
├── assets/               # Asset source files
├── node_modules/         # NPM dependencies
└── vendor/               # Composer dependencies
```

## Configuration

The application uses environment-based configuration:

Database configuration is located in `config/config.php`.

## Requirements

- PHP 8.1 or higher
- MySQL database
- PHP Composer
- Node.js and npm (used to install dependencies only)

### Data Grid Integration
- [phpGrid](https://phppgrid.com) library handles data display and datagrid operations

## Documentation

Comprehensive guides for using and developing Querro:

- **[Installation Guide](docs/INSTALLATION.md)** - Setup and deployment
- **[Configuration Guide](docs/CONFIGURATION.md)** - Configuration and customization
- **[Architecture Overview](README.md#architecture)** - System design
- **[FAQ](docs/FAQ.md)** - Frequently asked questions

## Installation

### Web Installer (Recommended)

The easiest way to get started — no command line required.

1. **Download** the latest release zip from the [Releases](https://github.com/phpcontrols/querro/releases) page
2. **Extract** the zip into your web server's document root (e.g. `/var/www/querro`)
3. **Open** `http://your-server/install.php` in your browser
4. **Follow** the 6-step wizard:
   - Requirements check
   - Database credentials
   - Database setup (imports schema automatically)
   - Application URL and environment
   - Create your admin account
   - Done — delete `install.php` when prompted

That's it. The installer writes your `.env`, creates the database, and sets up your admin user.

> **Note:** Delete `install.php` from your server after installation for security.

### Manual Install (Advanced)

For developers who prefer the command line or want to install from source, see the [Installation Guide](docs/INSTALLATION.md).

```bash
# Quick reference
git clone [repository-url] querro && cd querro
composer install && npm install
cd includes/phpGrid && composer install --ignore-platform-req=ext-gd && npm install && cd ../..
mysql -u root -p -e "CREATE DATABASE querro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p querro < install.sql
cp .env .env.local  # edit with your credentials
php bin/console cache:clear
```

## License

MIT License - See LICENSE file for details.

---

*Built with ❤️ using PHP, MySQL, and jQuery*
