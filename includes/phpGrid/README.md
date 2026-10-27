# phpGrid Lite

A PHP datagrid for showing, searching, sorting and exporting database tables, built on [free-jqGrid](https://github.com/free-jqgrid/jqGrid). Write a few lines of PHP and get a paged, sortable, searchable grid:

```php
<?php
use phpCtrl\C_DataGrid;
require_once("phpGrid_Lite/conf.php");

$dg = new C_DataGrid("SELECT * FROM orders", "orderNumber", "orders");
$dg->enable_search(true);
$dg->display();
```

## Requirements

- PHP 8.0 or later (tested up to PHP 8.5)
- A database supported by [ADOdb](https://adodb.org/): MySQL, PostgreSQL, SQL Server, Oracle, SQLite, DB2 and others
- Composer and npm, to install the PHP and JavaScript libraries

## Install

1. Put the `phpGrid_Lite` folder in your web root.
2. Install the libraries:

   ```sh
   cd phpGrid_Lite
   composer install --no-dev
   npm install --omit=dev
   ```

3. Open `install.php` in your browser to create `conf.php` and load the sample database, or copy `conf.sample.php` to `conf.php` and fill in your database settings.
4. Open `examples/index.php` to browse the demos.

Set `DEBUG` to `false` in `conf.php` before you deploy.

## Features

- Paging, sorting and column resizing
- Toolbar search and advanced search. Search values are sent to the database as bound parameters.
- Export to Excel and HTML
- Subgrids
- Grouping with group summaries
- Column formats: date, datetime, time, currency, number, link, dynamic link, image, email, checkbox
- Hidden, frozen and virtual columns; column chooser
- Local array data source and multiple database connections
- Themes: `modern` (default, needs no CSS framework), `bootstrap` and the jQuery UI themes
- Locales

## Full version

These need the full version of phpGrid ([phpgrid.com](https://phpgrid.com/)):

- Saving edits (add, edit, delete). Lite grids can show edit forms, but `edit.php` replies that saving needs the full version.
- Master/detail grids
- File upload, WYSIWYG editor, nested dropdowns
- Conditional formatting, data bars, pivot grids
- Global search, form-only mode, copy row, persisted grid state
- PDF and CSV export
- Bootstrap 4 and 5 themes, premium themes, PDO drivers
