var insert = true;
var ajax_call;
var selected_database = '';
var selected_table = '';
var all_tables = {};
var all_columns = {};

$(function() {

    //==================================================================================================
    //  SELECTBOXES
    //==================================================================================================

    $('.selectpicker').selectpicker({
        windowPadding: [64, 30, 30, 30],
        hideDisabled: true,
        noneSelectedText: ''
    });


    //==================================================================================================
    //  TOGGLES
    //==================================================================================================

    $('.toggle').toggles({
        drag: true,         // allow dragging the toggle between positions
        click: true,        // allow clicking on the toggle
        text: {
            on: 'AUTO',     // text for the ON position
            off: 'MANUAL'   // and off
        },
        on: true,           // is the toggle ON on init
        animate: 250,       // animation time (ms)
        easing: 'swing',    // animation transition easing function
        checkbox: '',       // the checkbox to toggle (for use in forms)
        clicker: null,      // element that can be clicked on to toggle. removes binding from the toggle itself (use nesting)
        width: 100,         // width used if not set in css
        height: 40,         // height if not set in css
        type: 'compact'     // if this is set to 'select' then the select style toggle will be used
    }).on('toggle', function(e, active) {
        if (active) {
            $(this).next().prop('checked', active);
            $(this).removeClass('inactive').addClass('active');
            $(this).parent().next().hide();
        } else {
            $(this).next().prop('checked', active);
            $(this).removeClass('active').addClass('inactive');
            $(this).parent().next().show();
        }
    });


    //==================================================================================================
    //  EVENTS LISTNERS
    //==================================================================================================

    // ADD DATABASE
    $("#btn-databases-add").on("click", function() {
        insert = true;
        selected_database = '';
        selected_table = '';

        $('#databases_container .dropdown-menu li').removeClass('active');

        $('#db_label, #db_server, #db_name, #db_username, #db_password').val('');
        $('#db_type').val('mysql').trigger('change'); // Trigger change to set port and charset

        refreshUI({"databases": "info", "tables": "msg-1", "columns": "msg-1"});

        $('#db_label').focus();
    });

    // DATABASE TYPE CHANGED - Update port and charset defaults
    $("#db_type").on("change", function() {
        $('#db_port').val('3306');
        $('#db_charset').val('utf8mb4');
    });

    // EDIT DATABASE
    $("#btn-databases-edit").on("click", function() {
        insert = false;

        $('#db_label').val(selected_databases[selected_database].label);
        $('#db_server').val(selected_databases[selected_database].server);
        $('#db_port').val(selected_databases[selected_database].port);
        $('#db_name').val(selected_databases[selected_database].name);
        $('#db_username').val(selected_databases[selected_database].username);
        $('#db_password').val(selected_databases[selected_database].password);
        $('#db_type').val(selected_databases[selected_database].type);
        $('#db_charset').val(selected_databases[selected_database].charset);

        refreshUI({"databases": "info", "tables": "msg-1", "columns": "msg-1"});
    });

    // CANCEL INSERT / EDIT
    $("#btn-databases-cancel").on("click", function() {
        $('#db_label, #db_server, #db_port, #db_name, #db_username, #db_password').val('');
        if (insert) {
            refreshUI({"databases": "add"});
        } else {
            showAllTables(selected_database);
            refreshUI({"databases": "on", "tables": "on", "columns": "msg-1"});
        }
    });

    // CONFIRM INSERT / EDIT
    $("#btn-databases-ok").on("click", function() {

        if (!$("#btn-databases-ok").hasClass('disabled')) {

            $("#btn-databases-ok").addClass('disabled');

            // ON INSERT WRITE A GOOD DATABASE KEY (database@server:port(-n))
            if (insert) {
                //selected_database = $('#databases_container .dropdown-menu li').length;

                selected_database = $('#db_name').val() + '@' + $('#db_server').val() + ':' + $('#db_port').val();
                var i = 2;
                while (selected_databases[selected_database] !== undefined) {
                    selected_database = $('#db_name').val() + '@' + $('#db_server').val() + ':' + $('#db_port').val() + '-' + i;
                    i++;
                }
            }

            selected_databases[selected_database] = {
                'active':   true,
                'label':    $('#db_label').val(),
                'server':   $('#db_server').val(),
                'port':     $('#db_port').val(),
                'name':     $('#db_name').val(),
                'username': $('#db_username').val(),
                'password': $('#db_password').val(),
                'type':     $('#db_type').val(),
                'charset':  $('#db_charset').val(),
                'encoding': (insert ? '' : selected_databases[selected_database].encoding)
            };

            // GET THE DATABASE CHARSET
            if (selected_databases[selected_database].encoding == '') {

                $.ajax({
                    type: "POST",
                    url: "/q/home.php?action=settings_getDBCharset",
                    data: {
                        'db_server':    selected_databases[selected_database].server,
                        'db_port':      selected_databases[selected_database].port,
                        'db_name':      selected_databases[selected_database].name,
                        'db_username':  selected_databases[selected_database].username,
                        'db_password':  selected_databases[selected_database].password,
                        'db_type':      selected_databases[selected_database].type,
                        'db_charset':   selected_databases[selected_database].charset
                    },
                    dataType: 'json'
                }).done(function( out ) {
                    if (out.status == 'success')
                        selected_databases[selected_database]['encoding'] = out.data.db_charset;
                });

            }

            // Get all columns of from all the tables and store in 'selected_databases';
            ajax_getAllTablesNColumns(selected_databases, selected_database, all_tables, all_columns);

        }
    });




    // DELETE DATABASE
    $("#btn-databases-delete").on("click", function() {
        $('#confirm-delete').modal();
    });
    $("#btn-databases-confirm-delete").on("click", function() {
        delete selected_databases[selected_database];
        var index = $("#databases_container .dropdown-menu li.active").index();
        $('#databases option').eq(index).remove();
        $('#databases_container .selectpicker').selectpicker('refresh');

        if (_.keys(selected_databases).length == 0)
            refreshUI({"databases": "msg-1", "tables": "msg-1", "columns": "msg-1"});
        else
            refreshUI({"databases": "add", "tables": "msg-1", "columns": "msg-1"});

        selected_database = '';
        selected_table = '';

        $('#confirm-delete').modal('toggle');
    });


    // DATABASE INITIAL LOAD
    $('#databases_container .selectpicker').on('loaded.bs.select', function (e) {
        $('#databases_container .dropdown-menu.open').append($('#databases_msg'));
        if (_.keys(selected_databases).length == 0)
            refreshUI({"databases": "msg-1"});
        //$('#databases_container .selectpicker').trigger('changed.bs.select', [$('#databases_container .dropdown-menu li.selected:eq(0)').index(), true, false]);
    });

    // DATABASE CLICKED
    $('#databases_container .selectpicker').on('changed.bs.select', function (event, clickedIndex, newValue, oldValue) {

        // clear any warning
        $('#alert-msg span').html('').parent().hide();
        
        //selected_database = clickedIndex;
        selected_database = $('#databases option').eq(clickedIndex).attr('value');

        var $this = $('#databases_container .dropdown-menu li').eq(clickedIndex);

        // ADD THE DOUBLE CLICK FUNCTION
        if (oldValue && !newValue && !$this.hasClass('active')) {
            var old_arr = $('#databases_container .selectpicker').selectpicker('val');
            var new_val = $('#databases option').eq(clickedIndex).val();

            if (old_arr === null)
                old_arr = [];
            old_arr.push(new_val);

            $('#databases_container .selectpicker').selectpicker('val', old_arr);
        }

        if ($this.hasClass('active')) {
            $this.removeClass('active');
            refreshUI({"databases": "add", "tables": "msg-1", "columns": "msg-1"});
        } else {
            $this.addClass('active').siblings().removeClass('active');
            refreshUI({"databases": "on", "tables": "on", "columns": "msg-1"});
        }

        // UPDATE THE ACTIVE OR INACTIVE DATABASES
        if (oldValue && !newValue && !$this.hasClass('active'))
            selected_databases[selected_database].active = false;
        if (!oldValue && newValue)
            selected_databases[selected_database].active = true;

        if ($this.hasClass('warning')) {
            refreshUI({"databases": "on", "tables": "msg-2", "columns": "msg-1"});
            return false;
        }

        // GET ALL TABLES OF THE SELECTED DATABASE
        var in_array = false;
        $.each(all_tables, function( key, value ) {
            //if(all_tables.hasOwnProperty(key) && key == selected_database)
            if (key == selected_database)
                in_array = true;
        });

        if (!in_array) {

            ajax_call = $.ajax({
                type: "POST",
                url: "/q/home.php?action=settings_getAllTables",
                data: {
                    'db_server':    selected_databases[selected_database].server,
                    'db_port':      selected_databases[selected_database].port,
                    'db_name':      selected_databases[selected_database].name,
                    'db_username':  selected_databases[selected_database].username,
                    'db_password':  selected_databases[selected_database].password,
                    'db_encoding':  selected_databases[selected_database].encoding,
                    'db_type':      selected_databases[selected_database].type,
                    'db_charset':   selected_databases[selected_database].charset
                },
                dataType: 'json'
            }).done(function( out ) {
                if (out.status == 'success') {
                    // DB found
                    all_tables[selected_database] = out.data;
                    updateAllTables(out.data);
                } else {
                    // DB not found
                    $this.addClass('warning');

                    all_tables[selected_database] = [];
                    $.each(selected_databases[selected_database]['tables'], function( key, value ) {
                        all_tables[selected_database].push(selected_databases[selected_database]['tables'][key]['properties'][0].name);
                    });
                }
                showAllTables(selected_database);
            });

        } else {
            if ($this.hasClass('active'))
                showAllTables(selected_database);
        }

    });


    function updateAllTables(tables) {
        var found;
        var create_all_tables = false;
        var new_table;
        var arr_tables = [];
        var corresponding_keys = [];

        if (selected_databases[selected_database]['tables'] !== undefined && _.isObject(selected_databases[selected_database]['tables'])) {
            // Delete elemens that are not on the DB
            $.each(selected_databases[selected_database]['tables'], function( key, value ) {
                //arr_tables.push(selected_databases[selected_database]['tables'][key]['properties'][0].name);
                arr_tables.push(key);
                found = false;
                for (var i = 0; i < tables.length; i++) {
                    //if (selected_databases[selected_database]['tables'][key]['properties'][0].name == tables[i]) {
                    if (key == tables[i]) {
                        found = true;
                        if (key != i)
                            corresponding_keys[key] = i;
                    }
                }
                //if(!found)
                    //delete selected_databases[selected_database]['tables'][key];
            });
            /*
            selected_databases[selected_database]['tables'] = selected_databases[selected_database]['tables'].filter(function( element ) {
                return element !== undefined;
            });
            corresponding_keys = corresponding_keys.filter(function( element ) {
                return element !== undefined;
            });

            // Reorder indexes
            var clone_tables = [];
            $.each(corresponding_keys, function( key, value ) {
                if (value !== undefined)
                    clone_tables[value] = selected_databases[selected_database]['tables'][key];
            });
            selected_databases[selected_database]['tables'] = clone_tables;
            */

        } else {
            selected_databases[selected_database]['tables'] = {};
            create_all_tables = true;
        }

        // Insert new tables
        var new_key = '';
        $.each(tables, function( key, value ) {

            found = false;
            $.each(arr_tables, function( key2, value2 ) {
                if (value2 == value) {
                    found = true;
                }
            });

            if (!found) {
                new_key = value;
                var i = 2;
                while (selected_databases[selected_database]['tables'][new_key] !== undefined) {
                    new_key = value + '-' + i;
                    i++;
                }

                new_table = {
                    'active':   create_all_tables ? true : false,
                    'label':    value,
                    'properties': [
                    {
                        'columns':  '*',
                        'keys':     [],
                        'name':     value
                    }
                    ]
                };
                selected_databases[selected_database]['tables'][new_key] = new_table;
            }

        });

    }


    function showAllTables(database) {
        var selected;
        var label;
        var options = '';

        if (_.isObject(selected_databases[database]['tables'])) {
            $('#tables_msg').hide();

            $.each(selected_databases[database]['tables'], function( key, value ) {
                selected = false;
                $.each(all_tables[database], function( key2, value2 ) {
                    if (value.properties[0].name == all_tables[database][key2]) {
                        if (value.active)
                            selected = true;
                        return false;
                    }
                });

                // get file name from url
                const url = window.location.pathname;
                const filename = url.substring(url.lastIndexOf('/')+1);

                // hack to only show tables selected from settings
                if (filename == 'query.php') {
                    if (value.active) {
                        options += '<option value="' + key + '"' + (selected ? ' selected="selected"' : '') + '>' + (value.label ? value.label : value.name) + '</option>';
                    }
                }else{
                    options += '<option value="' + key + '"' + (selected ? ' selected="selected"' : '') + '>' + (value.label ? value.label : value.name) + '</option>';
                }
            });
            $('#tables').html(options);

            $('#tables_container .selectpicker').selectpicker('refresh');

            if ($('#databases_container .dropdown-menu li').eq(selected_database).hasClass('warning'))
                $('#tables_container .dropdown-menu li').addClass('warning');

        } else {
            refreshUI({"tables": "msg-2"});
        }

    }


    // TABLE INITIAL LOAD
    $('#tables_container .selectpicker').on('loaded.bs.select', function (e) {
        $('#tables_container .dropdown-menu.open').append($('#tables_msg'));
        refreshUI({"tables": "msg-1"});
    });

    // TABLE BUTTONS CLICKED
    $("#btn-tables-select-all").on("click", function() {
        $('#tables_container .selectpicker').selectpicker('selectAll');
        $.each(selected_databases[selected_database]['tables'], function( key, value ) {
            selected_databases[selected_database]['tables'][key].active = true;
        });
    });
    $("#btn-tables-deselect-all").on("click", function() {
        $('#tables_container .dropdown-menu li').removeClass('active');
        $('#tables_container .selectpicker').selectpicker('deselectAll');
        $.each(selected_databases[selected_database]['tables'], function( key, value ) {
            selected_databases[selected_database]['tables'][key].active = false;
        });
        selected_table = '';
        refreshUI({"columns": "msg-1"});
    });

    // TABLE CLICKED
    $('#tables_container .selectpicker').on('changed.bs.select', function (event, clickedIndex, newValue, oldValue) {

        // On select all or deselect all
        if (clickedIndex === undefined)
            return false;

        //selected_table = clickedIndex;
        selected_table = $('#tables option').eq(clickedIndex).attr('value');

        var $this = $('#tables_container .dropdown-menu li').eq(clickedIndex);

        // ADD THE DOUBLE CLICK FUNCTION
        if (oldValue && !newValue && !$this.hasClass('active')) {

            var old_arr = $('#tables_container .selectpicker').selectpicker('val');
            var new_val = $('#tables option').eq(clickedIndex).val();

            if (old_arr === null)
                old_arr = [];
            old_arr.push(new_val);

            $('#tables_container .selectpicker').selectpicker('val', old_arr);
        }
        if ($this.hasClass('active')) {
            $this.removeClass('active');
            refreshUI({"columns": "msg-1"});
        } else {
            $this.addClass('active').siblings().removeClass('active');
            refreshUI({"columns": "on"});
        }

        // UPDATE THE ACTIVE OR INACTIVE TABLES
        var add_new_table = false;
        if (oldValue && !newValue && !$this.hasClass('active')) {
            selected_databases[selected_database]['tables'][selected_table].active = false;
        }
        if (!oldValue && newValue) {
            if (selected_databases[selected_database]['tables'][selected_table] === undefined)
                add_new_table = true;
            else
                selected_databases[selected_database]['tables'][selected_table].active = true;
        }

        // GET ALL COLUMNS OF THE SELECTED TABLE
        var in_array = false;
        $.each(all_columns[selected_database], function( key, value ) {
            if (key == selected_table)
                in_array = true;
        });

        if (!in_array) {

            ajax_call = $.ajax({
                type: "POST",
                url: "/q/home.php?action=settings_getAllColumns",
                data: {
                    'db_server':    selected_databases[selected_database].server,
                    'db_port':      selected_databases[selected_database].port,
                    'db_name':      selected_databases[selected_database].name,
                    'db_username':  selected_databases[selected_database].username,
                    'db_password':  selected_databases[selected_database].password,
                    'db_encoding':  selected_databases[selected_database].encoding,
                    'db_type':      selected_databases[selected_database].type,
                    'db_charset':   selected_databases[selected_database].charset,
                    'database':     selected_database,
                    'table':        selected_table
                },
                dataType: 'json'
            }).done(function( out ) {
                if (out.status == 'success') {

                    if (all_columns[selected_database] === undefined)
                        all_columns[selected_database] = {};
                    if (all_columns[selected_database][selected_table] === undefined)
                        all_columns[selected_database][selected_table] = out.data;

                    updateAllColumns(out.data, out.colMeta, selected_table);

                    if (add_new_table)
                        insertNewTable(selected_database, selected_table);
                } else {
                    // DB not found
                    //$this.addClass('warning');

                    if (all_columns[selected_database] === undefined)
                        all_columns[selected_database] = {};
                    if (all_columns[selected_database][selected_table] === undefined)
                        all_columns[selected_database][selected_table] = [];

                    if (selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns !== undefined && selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns !== '*' && selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns.length > 0) {
                        $.each(selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns, function( key, value ) {
                            all_columns[selected_database][selected_table].push(value.name);
                        });
                    }

                }

                showAllColumns(selected_database, selected_table);

            });

        } else {
            if ($this.hasClass('active'))
                showAllColumns(selected_database, selected_table);
        }

    });


    function updateAllColumns(columns, colMeta = false, selected_table = '') {
        var found;
        var create_all_columns = false;
        var new_column;
        var arr_columns = [];
        var corresponding_keys = [];

        if (selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'] !== undefined && _.isObject(selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'])) {

            // Delete elemens that are not on the DB
            $.each(selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'], function( key, value ) {
                //arr_tables.push(selected_databases[selected_database]['tables'][key]['properties'][0].name);
                arr_columns.push(value);
                found = false;
                for (var i = 0; i < columns.length; i++) {
                    //if(selected_databases[selected_database]['tables'][key]['properties'][0].name == columns[i]) {
                    if (value == columns[i]) {
                        found = true;
                        if (key != i)
                            corresponding_keys[key] = i;
                    }
                }
                //if(!found)
                    //delete selected_databases[selected_database]['tables'][key];
            });

        } else {
            selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'] = [];
            create_all_columns = true;
        }

        // Insert new tables
        $.each(columns, function( key, value ) {

            found = false;
            $.each(arr_columns, function( key2, value2 ) {
                if (value2.name == value) {
                    found = true;
                }
            });

            if (!found) {

                if (colMeta[value]['pk']) {
                    new_column = {
                        'label':    value,
                        'name':     value,
                        'active':   create_all_columns ? true : false,
                        'type':     colMeta[value]['metaType'],
                        'pk':       true
                    };
                } else {
                    
                    new_column = {
                        'label':    value,
                        'name':     value,
                        'active':   create_all_columns ? true : false,
                        'type':     colMeta[value]['metaType']
                    };
                }
                selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'].push(new_column);
            }

        });

    }


    function insertNewTable(selected_database, selected_table) {

        if (all_columns[selected_database] === undefined || all_columns[selected_database][selected_table] == undefined) {
            var columns_label_name = '*';
            all_columns[selected_database] =  [];
        } else {
            var columns_label_name = [];
            for (var i = 0; i < all_columns[selected_database][selected_table].length; i++) {
                columns_label_name.push({
                    'label': all_columns[selected_database][selected_table][i],
                    'name':  all_columns[selected_database][selected_table][i]
                });
            }
        }

        if (selected_databases[selected_database]['tables'] === undefined)
            selected_databases[selected_database]['tables'] = [];

        selected_databases[selected_database]['tables'][selected_table] = {
            'active':   true,
            'label':    selected_table,
            'properties': [
            {
                'columns':  columns_label_name,
                'keys':     [],
                'name':     selected_table
            }
            ]
        };

    }


    function showAllColumns(database, table) {
        var selected;
        var label;
        var options = '';
        var sel_db_table_index;

        $.each(selected_databases[database]['tables'][table]['properties'][0].columns, function( key, value ) {
            selected = false;
            $.each(all_columns[database][table], function( key2, value2 ) {
                if (value.name == value2) {
                    if (value.active)
                        selected = true;
                    return false;
                }
            });
            options += '<option data-type="' + value.type + '" value="' + value.name + '"' + (selected ? ' selected="selected"' : '') + '>' + (value.label ? value.label : value.name) + '</option>';
        });
        $('#columns').html(options);

        $('#columns_container .selectpicker').selectpicker('refresh');
        if ($('#databases_container .dropdown-menu li').eq(selected_database).hasClass('warning'))
            $('#columns_container .dropdown-menu li').addClass('warning');
    }

    $("#btn-columns-select-all").on("click", function() {
        selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns = '*';
        $('#columns_container .selectpicker').selectpicker('selectAll');
    });
    $("#btn-columns-deselect-all").on("click", function() {
        selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns = [];
        $('#columns_container .selectpicker').selectpicker('deselectAll');
    });

    // COLUMNS INITIAL LOAD
    $('#columns_container .selectpicker').on('loaded.bs.select', function (e) {
        $('#columns_container .dropdown-menu.open').append($('#columns_msg'));
        refreshUI({"columns": "msg-1"});
    });


    // COLUMNS CLICKED
    $('#columns_container .selectpicker').on('changed.bs.select', function (event, clickedIndex, newValue, oldValue) {
        var selected_column = clickedIndex;
        //var selected_column = $('#columns option').eq(clickedIndex).val();

        var $this = $('#columns_container .dropdown-menu li').eq(clickedIndex);

        if (selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns == '*') {
            selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns = [];
            for (var i = 0; i < all_columns[selected_database][selected_table].length; i++) {
                selected_databases[selected_database]['tables'][selected_table]['properties'][0].columns.push({
                    'label': all_columns[selected_database][selected_table][i],
                    'name': all_columns[selected_database][selected_table][i]
                });
            }
        }

        // UPDATE THE ACTIVE OR INACTIVE COLUMNS
        var add_new_table = false;
        if (oldValue && !newValue && !$this.hasClass('active')) {
            selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'][selected_column].active = false;
        }
        if (!oldValue && newValue) {
            selected_databases[selected_database]['tables'][selected_table]['properties'][0]['columns'][selected_column].active = true;
        }

    });


    // REFRESH UI
    // arr_options = {
    //      "databases":    "on" | "info" | "add" | "msg-1",
    //      "tables":       "on" | "msg-1" | "msg-2",
    //      "columns":      "on" | "msg-1" | "msg-2",
    // }
    function refreshUI(obj_options) {

        $.each(obj_options, function( index, value ) {

            if (value == 'msg-1' || value == 'msg-2') {
                if (value == 'msg-1' || value == 'msg-2') {
                    $('#' + index).html('');
                    $('#' + index + '_container .selectpicker').selectpicker('refresh');
                }
                $('#' + index + '_msg').html($('#' + index + '_msg').data(value)).show();
            } else if (value == 'on') {
                $('#' + index + '_msg').hide();
            }

            if (index == 'databases') {
                if (value == 'on') {
                    $('#database_info, #database_disabled').hide();
                    $('#btn-databases-add, #btn-databases-edit, #btn-databases-delete').show();
                } else if (value == 'info') {
                    $('#btn-databases-add, #btn-databases-edit, #btn-databases-delete').hide();
                    $('#database_info, #database_disabled').show();
                } else if (value == 'add' || value == 'msg-1') {
                    $('#btn-databases-edit, #btn-databases-delete,#database_info, #database_disabled').hide();
                    $("#btn-databases-add").show();
                }
            }

            if (index == 'tables') {
                if (value == 'on')
                    $('#btn-tables-select-all, #btn-tables-deselect-all').show();
                else
                    $('#btn-tables-select-all, #btn-tables-deselect-all').hide();
            }

            if (index == 'columns') {
                if (value == 'on')
                    $('#btn-columns-select-all, #btn-columns-deselect-all').show();
                else
                    $('#btn-columns-select-all, #btn-columns-deselect-all').hide();
            }

        });

    }


    //==================================================================================================
    //  SUBMIT FORM
    //==================================================================================================

    $('#btn-save').on('click', function() {

        // Uncomment the following line to submit the form using the defaultSubmit() method
        // $('#cie-form').formValidation('defaultSubmit');

        if (!$('#btn-save').hasClass('disabled')) {

            $('#btn-save').addClass('disabled');

            ajax_call = $.ajax({
                type: "POST",
                url: "/q/home.php?action=save_configurations",
                data: $('#cie-form').serialize() + '&databases=' + JSON.stringify(selected_databases),
                dataType: 'json'
            }).done(function( out ) {
                $('#btn-save').removeClass('disabled');
                if (out.status && out.status == 'error') {
                    $('#alert-msg span').html(out.message).parent().show();
                } else {
                    $('#modal-msg').modal();
                    $('#alert-msg span').html(out.message).parent().hide('fast');
                }

                // trigger ajaxStop so loader goes away with a delay
                setTimeout(() => {
                    $.event.trigger( "ajaxStop" );
                },1000)

            }).fail(function( out ) {
                $('#alert-msg span').html(out.message).parent().show();
                $('#btn-save').removeClass('disabled');
            });

        }

    });

    // After a while hides the modal
    $('#modal-msg').on('show.bs.modal', function() {
        var myModal = $(this);
        clearTimeout(myModal.data('hideInterval'));
        myModal.data('hideInterval', setTimeout(function() {
            myModal.modal('hide');
        }, 3000));
    });






            
    // GET ALL TABLES OF THE SELECTED DATABASE
    // 5/24/222 Richard: GET ALL COLUMN META WHEN ADDING A NEW DATABASE
    function ajax_getAllTablesNColumns(selected_databases, selected_database, all_tables, all_columns){

        $.ajax({
            type: "POST",
            url: "/q/home.php?action=settings_getAllTables",
            data: {
                'db_server':    selected_databases[selected_database].server,
                'db_port':      selected_databases[selected_database].port,
                'db_name':      selected_databases[selected_database].name,
                'db_username':  selected_databases[selected_database].username,
                'db_password':  selected_databases[selected_database].password,
                'db_encoding':  selected_databases[selected_database].encoding,
                'db_type':      selected_databases[selected_database].type,
                'db_charset':   selected_databases[selected_database].charset
            },
            dataType: 'json'
        }).done(function( out ) {

            var warning = false;
            if (out.status == 'success') {

                if (!$('#btn-save').hasClass('disabled')) $('#btn-save').addClass('disabled');

                all_tables[selected_database] = out.data;

                // all all column meta of the new database
                Array.from(Object.values(all_tables[selected_database])).forEach( theTable => {

                    $.post({
                        type: "POST",
                        url: "/q/home.php?action=settings_getAllColumns",
                        data: {
                            'db_server':    selected_databases[selected_database].server,
                            'db_port':      selected_databases[selected_database].port,
                            'db_name':      selected_databases[selected_database].name,
                            'db_username':  selected_databases[selected_database].username,
                            'db_password':  selected_databases[selected_database].password,
                            'db_encoding':  selected_databases[selected_database].encoding,
                            'db_type':      selected_databases[selected_database].type,
                            'db_charset':   selected_databases[selected_database].charset,
                            'database':     selected_database,
                            'table':        theTable
                        },
                        dataType: 'json',
                    }).done(function( out ) {

                        if (out.status == 'success') {

                            if (all_columns[selected_database] === undefined)
                                all_columns[selected_database] = {};
                            if (all_columns[selected_database][theTable] === undefined)
                                all_columns[selected_database][theTable] = out.data;

                            updateAllColumns(out.data, out.colMeta, theTable);

                        } else {
                            $('#alert-msg span').html(out.message).parent().show();
                        }

                    }).fail(function( err ) {
                        $('#alert-msg span').html(err.responseText).parent().show();
                    })

                });

            } else {
                // the DATABASE inserted or edited doesn't currently exist or the access data is wrong
                all_tables[selected_database] = [];
                warning = true;
            }

            updateAllTables(all_tables[selected_database]);

            if (insert) {
                $('#databases').append('<option value="' + selected_database + '" selected="selected">' + selected_databases[selected_database].label + '</option>');
                $('#databases_container .selectpicker').selectpicker('refresh');
            } else {
                var index = $("#databases_container .dropdown-menu li.active").index();
                $('#databases option').eq(index).attr('value', selected_database);
                $('#databases option').eq(index).html(selected_databases[selected_database].label);
                $('#databases_container .selectpicker').selectpicker('refresh');
                $('#databases_container .dropdown-menu li').eq(index).addClass('active');
            }

            if (warning) {
                $('#databases_container .dropdown-menu li').eq($('#databases option').length-1).addClass('warning');
                refreshUI({"databases": "on", "tables": "msg-2"});
            } else {
                $('#databases_container .dropdown-menu li').eq($('#databases option').length-1).addClass('active');
                showAllTables(selected_database);
                refreshUI({"databases": "on", "tables": "on"});
            }

            $("#btn-databases-ok").removeClass('disabled');

        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // ============================================================
    // AI ASSISTANT SETTINGS
    // ============================================================

    // Load AI settings on page load
    loadAISettings();

    // Toggle API key visibility
    $('#btn-toggle-api-key').on('click', function() {
        const input = $('#openai_api_key');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Save AI settings
    $('#btn-save-ai-settings').on('click', function() {
        const apiKey = $('#openai_api_key').val().trim();
        const model = $('#openai_model').val();

        if (!apiKey) {
            alert('Please enter your OpenAI API key');
            return;
        }

        // Basic validation
        if (!apiKey.startsWith('sk-')) {
            alert('Invalid API key format. OpenAI keys start with "sk-"');
            return;
        }

        $.ajax({
            type: "POST",
            url: "/q/actions.php?action=ai_saveSettings",
            data: {
                openai_api_key: apiKey,
                openai_model: model
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showAIStatus('Settings saved successfully!', 'success');
                    loadAIUsageLog();
                } else {
                    showAIStatus(response.message || 'Failed to save settings', 'danger');
                }
            },
            error: function() {
                showAIStatus('Error saving settings', 'danger');
            }
        });
    });

    // Test AI connection
    $('#btn-test-ai-connection').on('click', function() {
        const apiKey = $('#openai_api_key').val().trim();

        if (!apiKey) {
            alert('Please enter your OpenAI API key first');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');

        $.ajax({
            type: "POST",
            url: "/q/actions.php?action=ai_testConnection",
            data: {
                openai_api_key: apiKey
            },
            dataType: 'json',
            success: function(response) {
                $('#btn-test-ai-connection').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Test Connection');

                if (response.status === 'success') {
                    showAIStatus('Connection successful! API key is valid.', 'success');
                } else {
                    showAIStatus(response.message || 'Connection failed', 'danger');
                }
            },
            error: function() {
                $('#btn-test-ai-connection').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Test Connection');
                showAIStatus('Connection test failed', 'danger');
            }
        });
    });

    // Load AI settings
    function loadAISettings() {
        $.ajax({
            type: "POST",
            url: "/q/actions.php?action=ai_getSettings",
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    $('#openai_api_key').val(response.data.openai_api_key || '');
                    $('#openai_model').val(response.data.openai_model || 'gpt-4o');
                }
                loadAIUsageLog();
            }
        });
    }

    // Load usage log
    function loadAIUsageLog() {
        $.ajax({
            type: "POST",
            url: "/q/actions.php?action=ai_getUsageLog",
            dataType: 'json',
            success: function(response) {
                displayAIUsageLog(response.data || []);
            }
        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Display usage log
    function displayAIUsageLog(logs) {
        const tbody = $('#ai-usage-tbody');
        tbody.empty();

        if (logs.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center">No usage history yet</td></tr>');
            return;
        }

        logs.slice(0, 10).forEach(function(log) {
            const successBadge = log.success
                ? '<span class="label label-success">Success</span>'
                : '<span class="label label-danger">Failed</span>';

            tbody.append(`
                <tr>
                    <td>${log.created_at}</td>
                    <td>${escapeHtml(log.natural_language_input.substring(0, 80))}...</td>
                    <td>${log.model_used}</td>
                    <td>${successBadge}</td>
                </tr>
            `);
        });
    }

    // Show status message
    function showAIStatus(message, type) {
        const statusDiv = $('#ai-status-display');
        const statusMsg = $('#ai-status-message');

        statusDiv.removeClass('alert-success alert-danger alert-info');
        statusDiv.addClass('alert-' + type);
        statusMsg.text(message);
        statusDiv.fadeIn();

        setTimeout(function() {
            statusDiv.fadeOut();
        }, 5000);
    }

});