## phpGrid 7.5.5

* Bootstrap 5 support!
* Ajax file upload now works even when add new a row
* Add ability to read and save JSON as data source
* Added delete event handler to custom event handler demo
* New demo - read and write JSON to url
* Demo file upload to add event to Ajax call addtional script after submit(both add or edit)
* Demo - Locale settings to also added changes to datepicker

## phpGrid 7.5.3

* Start using Composer install for ADOdb, PHPSQLParser, more to follow
* Postgres complete sample db
* Real estate listing demo app
* bugfix: field_name() can returns false since orgname property doesn’t exist all db type
* compatible fix in php, count must not have null
* PDO_ODBC support (experimental)
* Update all demos to use phpCtrl from phpGrid namespace
* Improved tabbed detail grids example
* Removed hard coded <br> in grid bottom replace with CSS
* jqGrid modal to widen when using Bootstrap theme
* db->field_name() returns false when it is an alias (cannot be used in WHERE) as some dbs don’t support alias used in WHERE. this fix global search when alias used, but not with integrated search yet
* fontawesome 4.3->4.7
* Sql parse now also checks for run-time new connection settings
* demo update - remove caption for tabbed grid
* BUGFIX - set caption false css had no unique ID
* modify phpgrid enable width to use its container width, not BODY
* BUGFIX - $this->jq_colNames, set_colNames are descriptive name. col_dbnames are now used for actual database field name
* remove extra documnt ready in autowidth function
* Empty field is force to NULL with adodb_force_type

## phpGrid 7.5.2

* Nested/Cascadeing dropdown fix for both INLINE and FORM
* DB Chartset default to utf8mb4 (MySql)
* Added foreign character PDF EXPORT instruction on KB
* New installation script (with MySql demo)
* Migrating datepicker to flatpickr
* ADOdb 5.22 update
* Demo: conditional format to reload after each submit
* Demo: Keep edit form staying open after save
* Demo: Triple nested dropdown demo update
* Minor UI update to global search
* Sample select dataurl to use select_limit with 4th parameter for better security
* App: coinmarketcap clone

## phpGrid 7.5

* IBM i DB2 / AS400 support
* ADOdb 5.21 update (SSL connection support)
* Stored procedure support
* Consume external REST API via ws_edit.php (beta)
* Removed phpinfo script for security purpose
* Loading text is dynamic
* Added function set_grid_style() for simple grid styling header, color, and fonts
* Replace hard coded ‘edit.php’ cls_datagrid with $this->edit_file
* Fixed a bug in white_list() that cause drag and drop grouping fail 
* Modify set_caption() to pass false to hide caption header element
* Remove DejaVuSansCondensed.mtx.php, auto genearted file
* New demo: column alias 
* Demo explorer now have directly link via hash fragment as query parameter


## phpGrid 7.4.7

### Bugfixes
* PHPExcel PHP 7.4.1 update
* Persist column state enhancement and fixed a small bug that returns warning
* Fix to load on vertical scroll
* Force hover color 
* PDF export deprecating issue fix
* Increase setimeout to 500 from 200 in a form only mode
* multiselect dropdown in toolbar search need to also check value
* show scroll bar for long edit form (Bootstrap & JQuery UI)
* Fix the subgrid plus minus ill aligned 
* sql injection preventing using white_list() - added 3rd parameter to array_search, must check value ‘1’ as it’s the default value for ORDER BY.

### Demo
* Date format time only entry update, set range of seletable years back and future 50 yrs.


## phpGrid 7.4.6

This is an important bug fix release. Users are encouraged to upgrade to this version at earliest. 

### Bugfixes:
* Removed a deprecated magic method in utility class causing issues in PHP 7.4
* Revert a change in table namespace used for naming jqgrid index in colModel. In complex query, the best way to avoid column name ambiguity is still to use column alias.
* Improved mobile view with better CSS. Autoscroll is not supported in mobile view
* Remove generated fonts during pdf export from repo
* Included additional PDf fonts
* PHP 7.4 support - Deprecated: Array and string offset access syntax with curly braces is deprecated
* Update tfPDF to 1.8.2 for PHP 7.4
* Turn off autoresize caused edit form to cut off buttons in the bottom.
* Check isset col_edittypes[$colName]["multiple"]
* Fixed minor issues with row checkbox alignment in CSS
* Copy row bug fix due to constant 'id' replaced with JQGRID_ROWID

### Demo
* Improved master detail multiple tabbed demo


## phpGrid 7.4.5

This is mainly updates for bugfixes and security patches. It's HIGHLY recommended to update to this version.

### Bugfixes:
* Master detail inline add gives a modal error with unclear error messages
* Fixed a bug in guiStyle theme property settings where Bootstrap theme is not set correctly.
* columnchooser is working again. However, CC Cannot use with multiple select search filter at the same time. This limitation is due to conflicts from two different multiselects libraries are used for each feature. A fix will roll out in future updates
* Ensure there is always space between keywords in masterdetail which seems broke udpates in some edge cases.
* Include table namespace in SQL. Very usefully when users want to use JOINS with ambigious column names
* Set JQGRID_ROWID_KEY to '_rowid' other than default 'id' used by jqGrid. It can issues with master detail both has 'id' as the key, which causes the naming collision.  
* Fixed alternative row color CSS that wasn't working in Bootstrap themes.
* Position the dialog in the middle of window instead of (x, y)(0, 0)
* Fixed security issues in file upload due to folder name passed in POST. It is now set in conf.php. file upload API no longer accept 2nd and 3rd folder path values.

### Demo
* File upload demo is updated.


---


## phpGrid 7.4.1

### Features & Enhancements:

* Joomla 3 support enhancements and new tutorial
* Added multivalue select options in search
* Bootstrap4 support
* jqGrid 4.14.1

### BUGfix:

* fixed a bug in guiStyle setting
* Fixed masterdetail INLINE validating the variable has a truthy value or not.
* Fixed a typo in variable name
* Set search in virtual column to false
* dynamic link to display nothing when it�s blank or null
* Set initial dropdown size limited to 100 (from 10)

### Demos:
* Fixed an error in taggiing demo
* Math operation to include enable edit and jqGridAddEditAfterSubmit, so the total is updated in real time after each edit
* Demo update: autocomplete �ajaxDataUrl�


---


## phpGrid 7.4

### Enhancements:
* Updated ADOdb to 5.20.14
* Autocomplete via filtered Ajax JSON data URL (good for large dataset)
* Added sample code for returning JSON for ajax dropdown
* Add 100 years back to datepicker (default is 20)
* Clone/copy row now works on INLINE edit
* Remove &nbsp in EXCEL export So the numbers are automatically formatted as number in Excel
* Update Trumbowyg to the latest

### BUGfix:
* Export failed in local array data source. Do not call get_col_dbnames()
* Missing font files PDFTABLE (fixed)
* Master detail inline add new row works
* Trumbowyg enter key submit the form (fixed by e.stopPropagation)

### Demos
* Bootstrap integration to resize to parent window only after toggle
* Cell edit to include CELL Add and Delete
* Data format demo to include a hack to allow some countries that has data entry with a comma in currency
* Added export button to local array
* Change cell edit mode button export to use relative path
* tabbed grid demo update. Wwitched to Bootstrap tab from jquery ui tab


---


## phpGrid 7.3

### Features:
* PHP 7.3 support!
* Replaced jWYSIWYG with Trumbowyg due to jwysiwyg is no longer jQuery 3 compatible
* Better Notifications with real time server response

### Bugfix:
* Replace HAVING with WHERE when aggregate function exists so it will be compatible with non-MySql
* Column freeze - moved code block into event function so that is rendered after DOM is ready
* In PHP 7.3, using "continue" on a "switch" statement now throws a warning. Changed to �switch�
* Fixed a bootstrap CSS that displays extra bottom padding in toolbar search
* COUNT now throw warning when param is not Countable interface. Fixed.
*  check bootstrap if loaded had the wrong boolean. Fixed.
*  Fixed the weird box shadown in bootstrap theme

### Enhancements:
* Load Bootstrap CSS when it is not loaded.
* set_col_dynalink - paramter baseLinkUrl: when a link begins with 'http', or it is considered a column name

### Demos:
* Inventory management barcode tutorial - last part of the tutorial
* Fixed missing add icons in detail grid for inline edit
* Custom Action Buttons - Shopping Cart
* Master-detail layout with better CSS
* Bootstrap layout demo
* Freeze demo with search and add new
* Default to last page in freeze column demo
* New demo cell edit mode with add and delete


---


## phpGrid 7.2.9
This is a bugsfix release. 

### Bugfix: 
* Added missing .map files for Safari
* Returning proper empty JSON with 'die' in masterdetail, or 'undefined' error message shows up that overlays the master detail grid
* Upgraded jQuery Migrate 3.0.1 from 1.21
* Removed ending PHP closing tags
* Updated file upload script to be compatible with jQuery 3

### Demos:
* Multiple datagrid to take approx 1/4 of the screen height. 


---


## phpGrid 7.2.8

### Features:
* Datepicker, Datetimepicker, Timepicker are now automatically used based on the type returned from ADOdb Mysqli driver. Before it was only Datepicker by default. 
* Ready-made apps (Enterprise PLUS)
### Enhancements:
* Updated to the latest ADOdb
* Replace jQuery migrate 3 with 1.2.1 for better compatibility
### Bugfix:
* The detail grid loses initial filter and displays everything. Fixed.
* Removed extra padding around autocomplete in form display
* In edit type, set the select type empty value to literal �null� ONLY when the column is NOT required so it converts to database null.
* Master detail function has a warning message to overlay the page. Fixed.
* PHPExcel is no longer supported by OOS. However, still fixed a bug due to UTF-8 to delay finding an alternative library. 
* Fixed a bug in PHPSqlparser due to PHP 7 more�restrict rule on function signature. 
### Demos:
* New connection settings "sqlsrv" for Azure SQL server & native SQL Server support. This is the new recommended database type for SQL server on Windows & Azure.
* Local array data source with total
* Auto scroll


---


## phpGrid 7.2.7

### Features:
* Added Timepicker support. Requires column with SQL Time data type. For database has no TIME type support, you can use Sql Datetime type and choose to show time only
### Enhancements:
* Updated to jQuery 3.3.1 with migration file 3.0.1
### Bugfix:
* Fix a bug in PHPSqlParser for PHP 7 due to mixing arguments
* Updated demo db orders table to add logTime as �TIME� data type
* Trim single quotes for date comp in adv search
* Fixed bug in when exporting filter �date range�
* Added open and close brackets to sqlWhere after the filter or global search will break
### Demos:
* New demo that use a column to display text and hyperlink in another
* Enhanced date demo to include timepicker


---


## phpGrid 7.2.6

### Features:
* CELL edit mode for Excel-like behaviro
### Enhancements:
* Ability to handle very large datasets! Demo included.
* Set �url� to the current executing script to address variable scoping.
* Much improved Tabbed datagrid demo
* CSS white-space set to nowrap in cell edit mode
* Added NULL sellection support in dropdown option
### Security:
* Set property �autoencode� to prevent XSS 
### Theme:
* Enhanced Bootstrap theme
### Bugfix:
* Set a new theme at run-time did not overwrite a property when the original theme is "bootstrap". Fixed.
* Added child grid object validation when echo subgrid and master detail debug info. 
### Security:
### Demo:
* Tagging with autocomplete
* Excel edit (with keyboard navigation)
* Minor update to HTML Edit Control demo
* Load from large dataset (3M records!)
* Enhanced master detail demos
* Tagged grids demo improved with a separate loader


---


## phpGrid 7.2.5

### Features:
* New JOOMLA module (tested on 3+)
* New constant to check upload file extension
    define('UPLOADEXT', 'gif,png,jpg,jpeg');
### Enhancements:
* Change datetimepicker interval from default 1 hour to 30 min
* enable_autowidth to resize to body.width instead of window.width
* Set theme footer style
* updated to the latest ADODB5 library
### Theme:
* "bootstrap" - this adds official Twitter Bootstrap support!  jqgrid   guiStyle is changed to �bootstrap internally (guiStyle = �bootstrap�)
* Ability to use Bootstrap theme 
### Bugfix:
* Added foreign non-ASCII character utf-8 encode in data.php for MSSQL
* SET default height and width to �auto�. It fixes the bottom margin issue.
* Only to include bootstrap CSS file when theme name is �bootstrap�
* Columnchooser now works again (broken in 7.2 dueo to wrong ui.multiselect.js and .css)
* datetimepicker chinese locale fix by mappding 'cn' to 'ch'
* Added array boundary check that fixes "undefined in foreach().."" in cls_util.php
### Security:
* Run key through the whitelist to prevent SQL injection in subgrid.php, edit.php, ajaxfileupload.php, ajaxfiledelete.php
* New prepare statement support in database class
### Demo:
* Action column custom buttons


---


## phpGrid 7.2

### Features:
* CDN support. Default is false in conf.php 
* Added Bootstrap support
* Added Fontawesome icon support
* Drag and drop rows between datagrids
### Enhancements:
* Reduced Lux theme td padding and gap for subgrid
* Increase datagrid default height from 200px to 400px
* Update save selected row demo wording
### Bugfix:
* Hide horizontal scroll bar when enable_autowidth is set to true
* set_caption to convert blank to &nbsp;
### Demo:
* Load custom form from an iframe in the Modal window
* Display remaining characters used in a field on edit form
* Drag and drop rows between grids demo. Users must implement custom save function
* Bulk edit save sample code updated


---


## phpGrid 7.1.5
### Features
* jQuery rating supported (custom editing is WIP)
### Enhancements
* Subgrid add new populates linking key value autmoatically from the parent. 
* set_col_hidden() can take an array of string separated by comma. 
* New static $load_ajaxComplete flag to ensure the javascript ajaxComplete is only fired once.
### Bugfix
* Added missing comma in file upload javascript


---


## phpGrid 7.1
### Enhancement:
* set_masterdetail now has a 3rd parameter as the master foriegn key. Linking keys no longer need to be the same name!
* Pivot grid to handle large data set
* 5th parameter in set_group_properties to hide group details
### Bugfix:
* display_style internal function is now rendered after output buffer starts so styles won�t injected above HTML tag.
### Demo:
* Change calendar week start day
* Database administration demo


---


## phpGrid 7.0
### Form-only mode
* Blank form
* Load form from existing record
* Theme support
* Group header
* Tooltip
* Redirect after submit
- Wysiwyg color picker support
- CRUD success/error status display
- Session Manager
- Joomla! 3.x ready (FRAMEWORK)
- ADOdb 5.20 
### Bugfix: 
* Add new row in inline edit 
- New tabbed datagrid demo


---


## phpGrid 6.9
### Features
* Drag and Drop grouping support!
* Clone selected row
* PHP namespace full support!
* Persist column settings
* New conditional formatting with custom javascript demo


---


## phpGrid 6.7.10
### Features
* Added 2nd parameter in set_multiselect() so pagination will not clear selected row when set to true.   
### Bug fixes:
* Column sorting is working again
* INLINE edit to set focus on selected cell
### Demo udpate:
* Complex query support using array
### Framework update
* jqGrid 4.10.0


---


## phpGrid 6.7 
### Features
* PHP 7 support!
* PDF class updated to support PHP 7
* Native Excel export (requires PHPExcel)
* ADOdb 5.20 data access library update for PHP 7 support



---


## phpGrid 6.6
### Features
* Column auto resize based on contained contents
* Pivot grid support + new pivot grid example!
* Zend Framework, DB2 Support (new downloaded added to member portal)
* Virtual column positioning
* New date format (date-format.php) example
* New header tooltip function set_col_headerTooltip
* Footer icon now wraps when the width is too small
### Framework update
* jqGrid 4.9.2 - https://github.com/free-jqgrid/jqGrid
### Bug fixes
* Nested dropdown bugs. It�s now fully supported in both FORM and INLINE edit mode.
* Datepicker locale support 
* OTHER MINOR ONES


---


## phpGrid 6.53
### Features
* PDF export now support logo through enable_export 2nd parameter.

### Demo
* Complex query through local array

### Bug fix
* File upload field displays Delete button when adding a new record.
* Nested dropdown child select missing selected value during editing. Fixed.
* MS SQL composite key WHERE clause is handled differently to confirm to its syntax.
* CSS update to address ever expanding in edit form
* Remove inital horizontal scroll bar enable_autowidth() by calling setTimeout;
* Substract 18 from auto height instead of 10 so entire bar is revealed initally


---


## phpGrid 6.52
### Features
* Added set_col_datetime() method for datetime picker support

### Example
* Updated custom event handler example to show how to obtain auto-generated ID 
* Prefill filter in toolbar search demo: Integrated_search_prefilled_filter.php


---


## phpGrid 6.52b

### Features:
* Added 2nd parameter to in enable_search() for Excel like auto filter (local array data source only)
* Nested dropdown is now supported in FORM edit.

### Bug Fixes
* Added $this->jq_gridName to javascript function names in imageFormatter and imageUnformatter
* Required fields now display red * automatically

### Examples:
* Google Spreadsheet integration example!
* phpGrid and CodeIgniter Integration!


---


## phpGrid 6.51

### Features:
* New phpGrid session class for custom session handling.
* Integrated search is now available in detail grid.

### Exmaples
* Bootstrap integration example
* Laravel framework integration example


---


## phpGrid 6.5

### Core grid library has been updated to jqGrid 4.6.

### New Features:
* Global text search (not the same as full-text search)
* Math operation support such as column sum, avg. etc.

### New theme: 
* Introducing �cobalt-flat�. It�s flat and it�s awesome!

### Bug Fixes:
* Fixed a bug in CSS that causes the inline edit text box height to go beyond visible height in the new flat theme
* Added $this->jq_gridName to javascript function name imageFormatter/imageUnformatter.

### New example
* Externalize search
* Math operation 
* Global search


---


## phpGrid 6.5b

### New Features and Enhancements:
* Supports single level, multiple subgrid. This is an internal update. No new method was introduced. Use set_subgrid() to create multiple subgrids of the same master grid. Previously the later subgrid would overwrite the previous one. 

* Select and Autocomplete  edit types now support read only fields during edit, and only during edit. It doesn't affect during insert new row.

### Bug Fixes:
* Foreign key filter is now also applied during export master detail grids. This fixes bug that produces the entire details grids when export.
* Fix a bug that cause width resize to go crazy. It was changed from "100%" to '1000' so that subgrid width is stretched to fit the parent grid. It turns out it's more trouble than it worths. 
* New mobile example


### Misc.:
* Modified local_array.php to include sort type.
* Update inline action column, virtual column examples
* Reverted FORM edit trigger event back to "ondblClickRow" from "onSelectRow" as requested by users


---



## phpGrid 6.2

### Updated advanced example to include date comparison.
### Added set direction function
### Added a better datetimepicker jquery library to pick both date AND time.
### Page now reloads automatically as soon as a selection made in master grid by using �setSelection�.
### add tooltip using jQuery
### Conditional formatting now works with local array!!
### single quote in search and master detail join key value will break the grid. Fixed.
### New example - master detail layout side by side.
### Auto generate SERVER_ROOT value. When NOT using Apache alias directive or IIS virtual directory, phpGrid can successfully generate SERVER_ROOT value using the following script in conf.php. You don�t need to do anything. However, you should set SERVER_ROOT manually when use Apache alias directive or IIS virtual directory. 


---


## phpGrid 6.1

A bug in client side grid object referencing was fixed by using a unique jQuery selector ID and grid object variable name to avoid a naming collision. A 4th parameter was added to the grouping method to hide the grouping column by default. The grid IIS now auto reloaded on FORM edit after submitting. file_upload JavaScript was updated to display corresponding Ajax response in the debug console. A problem where required scripts were not included in an internal function due to execution orders between master and detail grids was fixed. The debug window is now toggled off by default. Two new code samples were added. 

You can now integrate phpGrid, phpChart and phpAutocomplete seamlessly in your solution! The integration sample code is now available online. phpGrid & phpChart Integration http://phpgrid.com/example/phpgrid-phpchart-integration-with-live-example/ phpGrid & phpAutocomplete Integration http://phpautocomplete.com/examples/phpgrid-integration-experimental/


---


## phpGrid 6

 This is a major release with many new features and enhancements. New features: composite primary key support, autocomplete control, sortable row, nested/drill-down editable subgrid, a column chooser, Excel mode, global theme and debug, two new great looking premium themes, a new row level permission function, and many bugfixes. The master detail feature has been massively enhanced. In the back-end, this release adds an SQL Server Native driver and DB2 DSN-less connectivity support.

- enable_edit

Added a 3rd parameter for user defined edit script file name other than the default "edit.php" file. Note that the user defined edit file is NOT automatically passed to detail and subgrid. In most cases, you do not need to use your own edit script rather than simply modify edit.php (requires Enterprise or Universal license for source code).

- set_col_dynalink

now works with local array


- cls_database.php

db2-dsnless

Now supports DB2 DSN-less as database type. Thanks, Jon Paris!

odbc_mssql_native

"odbc_mssql_native" DB_TYPE for SQL Server Native ODBC driver

"odbc_mssql" should be used for ***DSN-only connectivity *** on *nix, OS X using unixODBC through FreeTDS.

unixODBC requires environment variables in conf.php

  putenv("ODBCINSTINI=/usr/local/Cellar/unixodbc/2.3.1/etc/odbcinst.ini");

  putenv("ODBCINI=/usr/local/Cellar/unixodbc/2.3.1/etc/odbc.ini");



unixODBC is DSN connection only with the following .conf and .ini files. I used Amazon RDS MSSQL instance during testing.



   /usr/local/Cellar/freetds/0.91/etc/freetds.conf



   # server specific section

[global]

# TDS protocol version

; tds version = 4.2



# Whether to write a TDSDUMP file for diagnostic purposes

# (setting this to /tmp is insecure on a multi-user system)

; dump file = /tmp/freetds.log

; debug flags = 0xffff



# Command and connection timeouts

; timeout = 10

; connect timeout = 10



# If you get out-of-memory errors, it may mean that your client

# is trying to allocate a huge buffer for a TEXT field.

# Try setting 'text size' to a more reasonable limit

text size = 64512



[phpgridmssql]

host = phpgridmssql.cbdlprkhjrmd.us-west-1.rds.amazonaws.com

port = 1433

tds version = 7.0

   /usr/local/Cellar/unixodbc/2.3.1/etc/odbc.ini



   [phpgridmssql]

Description = MS SQL Server

Driver = FreeTDS

Server = phpgridmssql.cbdlprkhjrmd.us-west-1.rds.amazonaws.com

TraceFile = /tmp/sql.log

UID = mssqluser

PWD = PASSWORD

ReadOnly = No

Port = 1433

   Database = sampledb

   /usr/local/Cellar/unixodbc/2.3.1/etc/odbcinst.ini



   [FreeTDS]

Description = FreeTDS

Driver = /usr/local/Cellar/freetds/0.91/lib/libtdsodbc.so

Setup = /usr/local/Cellar/freetds/0.91/lib/libtdsodbc.so

FileUsage = 1

CPTimeout =

CPResuse =

client charset = utf-8



- SQL Server

The cells with NULL value repeat the value from the previous row has been fixed due to a bug in ADOdb (server/adodb5/adodb.inc.php), our database abstract class library.



- before_script_end public variable

Added public variable 'before_script_end'. It can be 'hooked' into the display when ALL the DOM elements are ready.



- priviate cust_prop_jsonstr (known as ud_params pre-version six)

example: filter_grid_on_pageload.php

Note that the filters must be passed as string to data.php via POST, so not possible to use set_grid_property function which takes only array params

Example:

$dg->cust_prop_jsonstr = 'postData: {filters:

                        \'{"groupOp":"AND","rules":[{"field":"status","op":"eq","data":"Shipped"}]}\'},';



- set_grid_property

Parameters:

$grid_property: An array represents grid property. The property will add to or overwrite existing properties already defined by phpGrid.

Description:

Advanced method. Set custom datagrid properties. The parameter must be an array. You must be rather familiar jqGrid API in order to take advantage of this method effectively. In most cases, you do not need to use this method. Note that this method is not the same as set_grid_method, another advanced phpGrid method.

Example:

$dg -> set_grid_property(array('search'=>true));



- set_grid_method

It now takes variable arguments. The changes make the function more flexible with different jqGrid methods with variable arguments. Super useful. :)



- INLINE edit improvement:

checkbox now display as checkbox,

new blank row when add.

auto refresh grid after add.

WYSIWYG support



- set_edit_condition

Set row-level edit condition for edition permission.

Note:

1. that this works for INLINE ONLY by hiding the edit icons using javascript, CSS

2. For that reason, developers should still validate user permission at the database or on the server side.

3. In more complicate condition, it's recommended to create your condition at run-time

Parameter:

 array(column => compare_operand, '&& OR ||', column2 => compare_operand, '&& OR ||'.....)

Usage example:

 $dg->enable_edit('INLINE', 'CRUD')->set_edit_condition(array('status'=>'!="Shipped"', '&&', 'customerNumber'=>'==129' ));

Example of generated javascript conditon:

 if $column = "status", $compare_operand = " == 'Shipped'", then it ouputs: if($("#orders").jqGrid("getCell", rowId, "status") == "Shipped"){



Example file: custom_edit_conditon.php



- Composite PK support (Enterprise+)

This is a major feature in version 6. Lots of development resources devoted to support composite PK, and yet making it simple at the same time. In stead of passing a single string variable in the constructor as the primary key, you can now pass an array of strings as the composite primary key. For a single primary key, you can still use a string or an array with a single string value. e.g.



http://phpgrid.com/example/composite-primary-key-support/



Note that composite PK is not supported for foreign key referencial in master/detail and subgrid.



- set_col_edittype

Use index number 0 and 1 instead of column name to retrieve data value in "select" edit type. This allows more complex SQL statement such as CONCAT.



- Conditional format

Fixed bug in subgrid due to "+" column



- set_masterdetail

The 2rd parameter is finally working as intended. It no longer has to be the same name as the master primary key. It was never implemented.

Í



- New premium themes! (Enterprise+)

aristo

cobalt

retro

You gonna like it!



- global theme support

THEME global constant in conf.php to set theme for all the grids. The global theme can be overwriten with set_theme



- load error display

added primvate loadError property. The error occurred during load will be displayed.



- set_sortablerow

activate sortable row. drag and drop row to sort.



- toolbar search

Changed default value from "equal" to "contain"



- jqGrid 4.5.2 support

Updated with latest jqGrid library



- Subgrid

Now support nested/drill-down subgrid!!



- FORM & INLINE edit

respects edit_options flags



- Autocomplete support!



- enable_columnchooser



- enable_autoheight

automatically resize based on window width - one step closer to an Excel-like editor.



- enabled_autoheight

Supported!



- datepicker

Display changeMonth and changeYear dropdown



- DEBUG globally constant

Server error now displays when DEBUG is true in conf.php



- conf.php (last one to

Add "PHPGRID" to prefix all DB constant to avoid potential name collisons with other systems and frameworks. eg. Wordpress uses DB_NAME variable.



phpGrid 5.5.5



This release addresses a few bugs and added a number of new features. The ODBC generic driver is now supported, as it works better in Unix environments for some databases such as MS SQL. A set_grid_method function was added. jqGrid 4.4.5, jQuery 1.9.0, and jQuery UI 1.10 are now supported. Column freeze is now supported. File upload can now be activated with a double click on a row in form edit mode. Minor bugs were fixed. 



phpGrid 5.5.2



File uploading now works when adding new a record. Conditional format is now working with rows without a preexisting cell format. Subgrid now supports filter methods.



phpGrid 5.5.1



This is a patch release. It fixes an array index out of bound bug in the set edittype function. It removes control validation logic for master/detail INLINE mode since this is no longer a limitation. It removes a redundant include statement in the datagrid class that was causing unnecessary errors in some systems. 



phpGrid 5.5



1. advanced search is now supported in detail grid

2. now support array parameter in set_col_dynalinks.It's also backward compatible.

$dynaParam (old $idName) can be a string or an array width dynamic value

$addParam are parameters with static value

3. display_script_includeonce scope is now public (better MVC framework compability)

$dg->display_script_includeonce(true);

4. better Oracle database support

define(PHPGRID_DB_TYPE, 'oci805');

5. updated to latest ADOdb library 5.1.8

6. added toolbar search dropdown support

7. phpChart integration example added!

8. PDF, CSV export format are now supported by requests!

9. performance optimzation with large datasets

10. now supports virtual columns, AKA caclulated field!

11. added support for custom validation by requests!

set_col_customrule

12. array data source is now (finally) supported!

export, subgrid and master detail grids are not supported

13. bug fix: Master Detail INLINE edit not working in master grid due to mulitple onSelectRow event handler.

14. bug fix: $grid not defined

15. bug fix: Only variable can have & since PHP5

16. bug fix: set_col_edittype now works with non-integer key

17. bug fix: conditional format when multiple select is true





phpGrid 5.0

0. upgrade to jqgrid 4.4

1. added column format type: date and checkbox

date attribute

    $dg->set_col_format('orderDate', "date", 

        array('srcformat'=>'Y-m-d','newformat'=>'n/j/Y'));

    //or

    // 3rd is the datepicker dateformat. Note the format is different

    $dg->set_col_date("orderDate", "Y-m-d", "n/j/Y", "m/dd/yy");

checkbox 

    $dg->set_col_format('isClosed', "checkbox");    // should only used for read only grid

2. added sqlite driver 

3. chained methods for:

setters 

enablers.

4. advanced methods:

set_col_property

add_event (new exmaple added)

5. set_col_edit_dimension

e.g. $dg->set_col_edit_dimension("comments", 50, 10);

e.g. $dg->set_col_edit_dimension("status", 40);

example: column_text_edit_dimension.php

6. file upload (beta)

edit must be enabled

FORM mode only

fiel system upload only, no BLOB

the file folder must allow write permission

One file upload supported per form

file name column should be able to allow NULL value

7. WYSIWYG example added

8. Search bug fix (OR operator)

9. 10 new custom themes, and old theme enhancement!

10. updated them roller example with dropdowns to change theme

11. Now support ability to call JavaScript function from hyperlink

Added "hyperlink_click_event" example: hyperlink onclick to call javascript function 

12. set_col_img

Added 2nd parameter to set base image URL. Only a single image base url is supported per grid
