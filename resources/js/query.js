var selected_database = '';
var selected_table = '';
var all_tables = {};
var all_columns = {};
var editor; // Global variable for ace editor
var editorMode = 'sql'; // 'sql' or 'nl' - tracks AI Assistant mode

// Helper function to truncate query names to 100 characters
function truncateQueryName(name, maxLength = 100) {
    if (!name) return '';
    return name.length > maxLength ? name.substring(0, maxLength) : name;
}

$(function() {

    // **************************************************************************************************
    // Auto-detect error notifications and apply red background
    // **************************************************************************************************

    // Monitor for new jGrowl notifications
    const jGrowlObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.classList && node.classList.contains('jGrowl-notification')) {
                    // Check if header contains "Error" (case-insensitive)
                    const header = node.querySelector('.jGrowl-header');
                    if (header && header.textContent.toLowerCase().includes('error')) {
                        node.classList.add('jgrowl-error');
                    }
                }
            });
        });
    });

    // Start observing the jGrowl container
    setTimeout(function() {
        const jGrowlContainer = document.querySelector('.jGrowl');
        if (jGrowlContainer) {
            jGrowlObserver.observe(jGrowlContainer, {
                childList: true,
                subtree: true
            });
        }
    }, 100);

    // **************************************************************************************************
    // select the first database from list of databases
    // **************************************************************************************************

    // remove selected if any; select first option; refresh; update selectpicker <li> via bs.select
    $('#databases option[selected="selected"]').removeAttr('selected')
    $("#databases option:first").attr('selected','selected');
    $('#databases.selectpicker').selectpicker('refresh')
    $('#databases_container .selectpicker').trigger('changed.bs.select', [$('#databases_container .dropdown-menu li.selected:eq(0)').index(), true, false]);


    // **************************************************************************************************
    // custom sql query editor
    // **************************************************************************************************

    editor = ace.edit("ace");

    editor.setTheme("ace/theme/xcode");
    editor.getSession().setMode("ace/mode/mysql");
    //editor.renderer.setShowGutter(false);

    // enable autocompletion and snippets
    editor.setOptions({
        maxLines: 20,
        minLines: 5,
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: true
    });

    // set font size
    document.getElementById('ace').style.fontSize = '13px';

    // **************************************************************************************************
    // AI Assistant Mode Toggle
    // **************************************************************************************************

    console.log('Setting up AI mode toggle handlers...');
    console.log('NL button exists:', $('#btn-nl-mode').length > 0);
    console.log('SQL button exists:', $('#btn-sql-mode').length > 0);

    // Toggle to Natural Language Mode
    $('#btn-nl-mode').on('click', function() {
        console.log('Natural Language Mode button clicked');
        editorMode = 'nl';

        // Update button states
        $(this).removeClass('btn-default').addClass('btn-primary active');
        $('#btn-sql-mode').removeClass('btn-primary active').addClass('btn-default');

        // Update mode indicator
        $('#mode-indicator').removeClass('label-primary').addClass('label-info').text('Natural Language Mode');

        // Change ACE editor to plain text mode (remove syntax highlighting)
        editor.getSession().setMode("ace/mode/text");

        // Update Run Query button text
        $('#btnRunQuery').html('<i class="fa fa-magic"></i> Generate SQL');

        // Clear editor if it has default SQL
        var currentValue = editor.getValue().trim();
        if (currentValue.toLowerCase().startsWith('select * from')) {
            editor.setValue('');
        }

        console.log('Switched to Natural Language Mode');
    });

    // Toggle to SQL Mode
    $('#btn-sql-mode').on('click', function() {
        console.log('SQL Mode button clicked');
        editorMode = 'sql';

        // Update button states
        $(this).removeClass('btn-default').addClass('btn-primary active');
        $('#btn-nl-mode').removeClass('btn-primary active').addClass('btn-default');

        // Update mode indicator
        $('#mode-indicator').removeClass('label-info').addClass('label-primary').text('SQL Mode');

        // Restore SQL syntax highlighting
        editor.getSession().setMode("ace/mode/mysql");

        // Restore Run Query button text
        $('#btnRunQuery').html('<i class="fa fa-play"></i> Run Query');

        console.log('Switched to SQL Mode');
    });

    // **************************************************************************************************
    // run custom query
    $('#btnRunQuery').on('click', function () {
        let cquery = editor.getValue();
        let $input = $(this).closest('form').find('#cquery');  // hidden input
        let db = $('#databases').val();
        let queryName = $('#queryName').text()

        console.log('cquery', cquery);

        if (!(cquery.trim())) {
            $.jGrowl('Please specify query first!', { sticky: false, header: 'Error', theme: 'error' });
            return false;
        }

        // Check if in Natural Language mode
        if (editorMode === 'nl') {
            generateSQLFromNaturalLanguage(cquery, db);
            return false;
        }

        // SQL Mode - execute query normally
        $input.val(cquery);

        $.post( "/q/grid-preview.php", { db: selected_database, tableName: selected_table, cquery: cquery, queryName: queryName } )
        .done(function( data ) {
           $('#datagrid').html(data);
        })

    });

    // Load existing query from db when queryId existing during init page load
    let queryId = $('input#queryId').val();
    if (queryId) {
        $.post( "/q/query-get.php", { queryId:  queryId } )
        .done(function( data ) {
            try {
                data = JSON.parse(data);

                // Remove dangerous control characters but preserve newlines, carriage returns, and tabs
                const sanitizedQuery = data.query.replace(/[\u0000-\u0008\u000B-\u000C\u000E-\u001F\u007F-\u009F]/g, '');
                editor.setValue(sanitizedQuery);

                // Truncate query name to 100 characters for display
                let displayName = truncateQueryName(data.name);
                $('#queryName').text(displayName).attr('title', data.name);
                $('#newQueryName').val(truncateQueryName(data.name));

                var $input = $(this).closest('form').find('#cquery');  // hidden input
                $input.val(data.query);

                $('#databases option[selected="selected"]').removeAttr('selected')
                $(`#databases option:contains(${data.db})`).attr('selected','selected');
                $('#databases.selectpicker').selectpicker('refresh')

                const index = $(`#databases option[value="${data.db}"`).index();
                $('#databases_container .selectpicker').trigger('changed.bs.select', [$("#databases_container .dropdown-menu li").eq(index).index(), true, false]);
                $('#databases.selectpicker').selectpicker('val', data.db);

                $('#embedcode').text('<iframe id="myData" name="myData" src="/q/view.php?sk=' + data.shareKey + '"; allowtransparency="true" style="width:100%;min-height:236px;border:none;overflow-y:hidden;overflow-x:auto;"></iframe>'); 
                $('#sharedViewPreviewLink').attr('href', encodeURI('/q/view.php?sk=' + data.shareKey));

                // run query
                $('#btnRunQuery').trigger('click');

            } catch (ex) { 
                //$.jGrowl(data, { sticky: false, header: '' });
                console.log('something went wrong with query-get');
            }
        });
    } else {
        $('#queryName').text('Query');
    }

    // Show save query OR update query button based on existence of query id
    if ($('input#queryId').val()) {
        $('#btnSaveQuery').hide();
        $('#btnUpdateQuery').show();
    } else {
        $('#btnSaveQuery').show();
        $('#btnUpdateQuery').hide();
    }

    // Save query
    $('#btnSaveQuery').on('click', function () {

        let query = editor.getValue().trim();
        let queryName = $('#newQueryName').val();
        
        if (query) {
            $.post( "/q/query-save.php", { queryName: queryName, cquery: query, db: $('#databases').val() } )
            .done(function( data ) {
                try {
                    data = JSON.parse(data);

                    // check if valid JSON and is inserted
                    if (data && typeof data === "object" && data.id > 0) {
                        // save new id to hidden input
                        $('input#queryId').val(data.id); 
                        $('#embedcode').text('<iframe id="myData" name="myData" src="/q/view.php?sk=' + data.shareKey + '"; allowtransparency="true" style="width:100%;min-height:236px;border:none;overflow-y:hidden;overflow-x:auto;"></iframe>'); 
                        $('#sharedViewPreviewLink').attr('href', encodeURI('/q/view.php?sk=' + data.shareKey));
                        $('#btnSaveQuery').hide();
                        $('#btnUpdateQuery').show();
                        $('#queryLastModified').text('Last time modified: ' + new Date().toLocaleString());
                        $('#btnShareView').prop('disabled', false);
                        $.jGrowl('Query saved!', { sticky: false, header: '' });

                        // refresh saved query list
                        loadSavedQuery();
                    }
                } catch (ex) { 
                    $.jGrowl(data, { sticky: false, header: '' });
                }
            });
        } else {
            alert('Your query is empty!')
        }
    });

     // Set current query name in modal for first save
     $('#newQueryName').val(truncateQueryName($('#queryName').text()));

    // Update query
    $('#btnUpdateQuery').on('click', function () {
        let query = editor.getValue().trim();
        let queryId = $('input#queryId').val();
        
        $.post( "/q/query-update.php", { queryId: queryId, cquery: query } )
         .done(function( data ) {
            try {
                data = JSON.parse(data);

                // check if valid JSON and current Id is returned
                if (data && typeof data === "object" && data.id == queryId) {
                    $.jGrowl('Query updated!', { sticky: false, header: '' });
                }

                $('#queryLastModified').html('Last time modified: Just now.');

                // refresh saved query list
                loadSavedQuery();

            } catch (ex) { 
                $.jGrowl(data, { sticky: false, header: '' });
            }
         });
    });

    // Rename query in modal
    $('#btnRenameQueryInModal').on('click', function() {

        let newQueryName = $('#newQueryName').val().trim();
        let queryId = $('#queryId').val();

        if (!newQueryName) {
            
            alert('The sql query name cannot be blank.');
            
        } else {

            // don't send to Ajax yet when it's a new query
            if (queryId) {
                $.post( "/q/query-rename.php", { queryName: newQueryName, queryId: queryId } )
                .done(function( data ) {
                    try {
                        data = JSON.parse(data);

                        // check if valid JSON and current Id is inserted
                        if (data && typeof data === "object" && data.id == queryId) {
                            $.jGrowl('Rename successful!', { sticky: false, header: '' });

                            // Truncate query name to 100 characters for display
                            let displayName = truncateQueryName(newQueryName);
                            $('#queryName').text(displayName).attr('title', newQueryName);
                            $('#renameQueryModal').modal('toggle');
                        }
                    } catch (ex) { 
                        $.jGrowl(data, { sticky: false, header: '' });
                    }
                });
            } else {
                // Truncate query name to 100 characters for display
                let displayName = truncateQueryName(newQueryName);
                $('#queryName').text(displayName).attr('title', newQueryName);
                $('#renameQueryModal').modal('toggle');
            }

        }
    });

    // Share view button to shared current query with a sharekey hash
    $('#btnShareView').on('click', function(e){

        let table = $('#tables').val();
        let query = 'SELECT * FROM ' + table;
        let queryName = table.toUpperCase();
        let queryId = $('input#queryId').val();
        
        if (queryId) {
            $.post( "/q/query-update-sharekey.php", { queryId: queryId, shared: true } )
            .done(function( data ) {
                try {
                    data = JSON.parse(data);

                    // check if udpate success
                    if (data.id > 0) {

                        // toggle buttons
                        $('#btnShareView').addClass('hide');
                        $('.shareViewBtns').removeClass('hide');

                        // update sharekey in embed code and preview link                        
                        let domain = $('#sharedViewPreviewLink').data('domain');

                        $('#sharedViewPreviewLink').attr('href',  domain + encodeURI('/q/view.php?sk=' + data.shareKey));
                        document.getElementById('embedcode').innerText = '<iframe id="myData" name="myData" src="' + domain + '/q/view.php?sk=' + data.shareKey + '" allowtransparency="true" style="width:100%;min-height:236px;border:none;overflow-y:hidden;overflow-x:auto;"></iframe>;';
                    }
                } catch (ex) { 
                    $.jGrowl(data, { sticky: false, header: '' });
                }
            });

        } 

    });

    // Stop sharing view by set share_key to empty
    $('#btnStopShareView').on('click', function(e){

        let queryId = $('input#queryId').val();
        
        if (queryId) {
            $.post( "/q/query-update-sharekey.php", { queryId: queryId, shared: false } )
            .done(function( data ) {
                try {
                    data = JSON.parse(data);

                    // check if udpate success
                    if (data.id > 0) {

                        // // toggle buttons
                        $('#btnShareView').removeClass('hide');
                        $('.shareViewBtns').addClass('hide');
                        
                    }
                } catch (ex) { 
                    $.jGrowl(data, { sticky: false, header: '' });
                }
            });
        } else {
            alert('Your query is empty!')
        }
    });
    

    // shareViewModal modal close event
    $('#shareViewModal').on('hidden.bs.modal', function () {
        $('#btnCopyEmbedCode').html('<i class="fa fa-copy"></i>');
    })

    // DATABASE INITIAL LOAD
    $('#databases_container .selectpicker').on('loaded.bs.select', function (e) {
        $('#databases_container .dropdown-menu.open').append($('#databases_msg'));
        $('#databases_container .selectpicker').trigger('changed.bs.select', [$("#databases_container .dropdown-menu li").eq(0).index(), true, false])

        if (_.keys(selected_databases).length == 0)
            refreshUI({"databases": "msg-1"});

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


        ajax_call = $.ajax({
            type: "POST",
            url: "/q/home.php?action=query_getAllTables",
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
                showAllTables(selected_database);

                // Reload saved queries for the selected database
                loadSavedQuery();
            }
        });
    });

    // TABLE INITIAL LOAD
    $('#tables_container .selectpicker').on('loaded.bs.select', function (e) {

        setTimeout(function() {

            $('#tables_container .dropdown-menu.open').append($('#tables_msg'));
            //refreshUI({"tables": "msg-1"});

            // Check if there are any tables to load
            var tableCount = $('#tables option').length;

            if (tableCount > 0) {
                console.log('Auto-selecting first table, count:', tableCount);

                // Select first table in both the select and selectpicker
                $('#tables').prop("selectedIndex", 0);
                var firstTableValue = $('#tables option:first').val();
                $('#tables.selectpicker').selectpicker('val', firstTableValue);
                $('#tables.selectpicker').selectpicker('refresh');

                // Set global variables
                selected_database = $('#databases.selectpicker :selected').val();
                selected_table = firstTableValue;

                console.log('Selected DB:', selected_database, 'Selected Table:', selected_table);

                // Manually trigger all the UI updates and load the table
                $('#tables_container .dropdown-menu li:first').addClass('active').siblings().removeClass('active');
                refreshUI({"databases": "on", "tables": "on", "columns": "msg-1"});

                // Reset query name
                $('#queryName').text('Query');
                $('#newQueryName').val('Query');
                $('#queryLastModified').text('');

                // Load the table preview directly
                loadSelectedTable();

                // Also trigger the change event for any other listeners
                $('#tables_container .selectpicker').trigger('changed.bs.select', [0, true, false]);
            }

        }, 800);

    });

    // TABLE CLICKED
    $('#tables_container .selectpicker').on('changed.bs.select', function (event, clickedIndex, newValue, oldValue) {

        // On select all or deselect all
        if (clickedIndex === undefined)
            return false;

        //selected_table = clickedIndex;
        selected_table = $('#tables option').eq(clickedIndex).attr('value');

        var $this = $('#tables_container .dropdown-menu li').eq(clickedIndex);

        // add active class for the selected row 
        $this.addClass('active').siblings().removeClass('active');
        refreshUI({"databases": "on", "tables": "on", "columns": "msg-1"});

        // load column props when table clicked
        loadSelectedTable();

        // reset custom query name to 'query'
        $('#queryName').text('Query');
        $('#newQueryName').val('Query');
        $('#queryLastModified').text('');

        ajax_call = $.ajax({
            type: "POST",
            url: "/q/home.php?action=query_getAllColumns",
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

                showAllColumns(selected_database, selected_table);
            }
        });
         
        // Reset query update button to save button and remove queryId
        $('#btnUpdateQuery').hide();
        $('#btnSaveQuery').show();
        $('input#queryId').val('');

        $('#btnShareView').removeClass('hide').prop('disabled', true);
        $('.shareViewBtns').addClass('hide');

    });

    // COLUMNS INITIAL LOAD
    $('#columns_container .selectpicker').on('loaded.bs.select', function (e) {
        $('#columns_container .dropdown-menu.open').append($('#columns_msg'));
        refreshUI({"columns": "msg-1"});
    });

    // COLUMNS CLICKED
    $('#columns_container .selectpicker').on('changed.bs.select', function (event, clickedIndex, newValue, oldValue) {
        // add active class for the selected row 
        var $this = $('#columns_container .dropdown-menu li').eq(clickedIndex);

        $this.addClass('active').siblings().removeClass('active');
        //refreshUI({"databases": "on", "tables": "msg-2", "columns": "msg-1"});
    });

     // QUERY CLICKED
    $('#queries').on('changed.bs.select', function (event, clickedIndex, newValue, oldValue) {
        let selectedQueryId = $(this).val();
        loadQueryById(selectedQueryId);
    });

    
    loadSavedQuery();
});

function escapeString(str) {
    return str
        .replace(/\\/g, '\\\\')   // Escape backslashes
        .replace(/"/g, '\\"')     // Escape double quotes
        .replace(/\n/g, '\\n')    // Escape newlines
        .replace(/\r/g, '\\r')    // Escape carriage returns
        .replace(/\t/g, '\\t');   // Escape tabs
}


// LOAD SELECTED TABLE IN DATAGRID
function loadSelectedTable() {

    let selected_database = $('#databases.selectpicker :selected').val();
    let selected_table = $('#tables.selectpicker :selected').val();

    // set sql select in query editor
    editor.setValue(`select * from ${selected_table}`)

    $.post( "/q/grid-preview.php", { db: selected_database, tableName: selected_table } )
    .done(function( data ) {
       $('#datagrid').html(data);
    })
};

// Function to load a query by ID (globally accessible)
function loadQueryById(selectedQueryId) {
    if (selectedQueryId) {
        $.post("/q/query-get.php", { queryId: selectedQueryId })
            .done(function (data) {
                try {
                    data = JSON.parse(data);

                    if (data && typeof data === "object" && data.query) {
                        // Remove dangerous control characters but preserve newlines (\n=0A), carriage returns (\r=0D), and tabs (\t=09)
                        const sanitizedQuery = data.query.replace(/[\u0000-\u0008\u000B-\u000C\u000E-\u001F\u007F-\u009F]/g, '');
                        editor.setValue(sanitizedQuery, -1); // -1 moves cursor to the start

                        // Populate hidden queryId field
                        $('input#queryId').val(selectedQueryId);

                        // Hide save query button
                        $('#btnSaveQuery').hide();

                        // Enable update query button
                        $('#btnUpdateQuery').show();

                        // Update last modified
                        $('#queryLastModified').text(`Last time modified: ${data.date_modified}`);

                        // Set query name in newQueryName label and input text
                        // Truncate query name to 100 characters for display
                        let displayName = truncateQueryName(data.name);
                        $('#queryName').text(displayName).attr('title', data.name);
                        $('#newQueryName').val(truncateQueryName(data.name));

                        // Share button
                        if (data.shareKey) {
                            $('#btnShareView').addClass('hide');
                            $('.shareViewBtns').removeClass('hide');
                        } else {
                            $('#btnShareView').removeClass('hide').prop('disabled', false);
                            $('.shareViewBtns').addClass('hide');
                        }
                    }
                } catch (ex) {
                    console.error('Error parsing query data:', ex);
                }
            });
    }
}

// Load query list and populate dropdown
function loadSavedQuery(targetElm = '#savedQuery') {
    // Get currently selected database
    let currentDb = $('#databases').val();

    // Build URL with database filter parameter
    let url = "/q/query-list.php";
    if (currentDb) {
        url += "?db=" + encodeURIComponent(currentDb);
    }

    $.getJSON(url, function (queries) {
        let dropdown = $('#queries'); // Replace with your dropdown ID
        dropdown.empty(); // Clear existing options

        queries.forEach(query => {
            // Truncate query name to 100 characters
            let truncatedName = truncateQueryName(query.name);

            // Escape HTML to prevent XSS
            let escapedName = $('<div>').text(truncatedName).html();
            let escapedFullName = $('<div>').text(query.name).html();
            let escapedQuery = $('<div>').text(query.query).html();

            // Create option with query name, delete button, and query text in badge
            let optionContent = `
                <div>
                    <div style="display: flex; align-items: flex-start; width: 100%; margin-bottom: 5px;">
                        <span class="query-name-text" data-query-id="${query.id}" title="${escapedFullName}" style="cursor: pointer;">${escapedName}</span>
                        <span class="query-delete-btn" data-query-id="${query.id}" data-query-name="${escapedFullName}" style="margin-left: 8px; cursor: pointer; color: #d9534f; flex-shrink: 0; font-size: 16px;" title="Delete query">×</span>
                    </div>
                    <span class='badge'>${escapedQuery}</span>
                </div>
            `;

            let option = $('<option>')
                .attr('data-content', optionContent)
                .attr('value', query.id)
                .attr('title', query.query)
                .addClass('code text-sm truncate query-list')
                .text(query.name);

            dropdown.append(option);
        });

        dropdown.selectpicker('refresh'); // Refresh if using Bootstrap Select

        // Add event handler for delete buttons and query name clicks
        setTimeout(function() {
            // Delete button handler
            $('.bootstrap-select .dropdown-menu').off('click', '.query-delete-btn').on('click', '.query-delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const queryId = $(this).data('query-id');
                const queryName = $(this).data('query-name');

                if (confirm(`Are you sure you want to delete "${queryName}"?`)) {
                    deleteQuery(queryId);
                }
            });

            // Query name click handler - ensures single query can be clicked
            // Try multiple selectors to catch the click event
            const $dropdown = $('#queries_container .dropdown-menu');

            // Handler for query name clicks
            $dropdown.off('click', '.query-name-text').on('click', '.query-name-text', function(e) {
                console.log('Query name clicked');
                e.preventDefault();
                e.stopPropagation();

                // Get the query ID from the data attribute
                const queryId = $(this).data('query-id');
                console.log('Query ID:', queryId);

                if (queryId) {
                    // Set the select value
                    $('#queries').val(queryId);
                    $('#queries').selectpicker('refresh');

                    // Manually trigger the query load
                    loadQueryById(queryId);

                    // Close the dropdown
                    $('#queries').selectpicker('toggle');
                }
            });

            // Also add a handler on the entire list item as a fallback
            $dropdown.off('click', 'li a').on('click', 'li a', function(e) {
                // Only handle if not clicking delete button
                if ($(e.target).hasClass('query-delete-btn')) {
                    return;
                }

                // Find the query ID from the query-name-text in this item
                const $queryName = $(this).find('.query-name-text');
                const queryId = $queryName.data('query-id');

                console.log('List item clicked, Query ID:', queryId);

                if (queryId) {
                    // Small delay to let Bootstrap Select update
                    setTimeout(function() {
                        loadQueryById(queryId);
                    }, 50);
                }
            });
        }, 100);
    });
}

// Delete query function
function deleteQuery(queryId) {
    $.ajax({
        url: '/q/query-delete.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id: queryId }),
        success: function(response) {
            $.jGrowl('Query deleted successfully!', { sticky: false, header: 'Success' });

            // Refresh the query list
            loadSavedQuery();

            // If the deleted query was currently open, clear the editor
            const currentQueryId = $('input#queryId').val();
            if (currentQueryId == queryId) {
                editor.setValue('');
                $('#queryName').text('Query');
                $('#newQueryName').val('Query');
                $('input#queryId').val('');
                $('#btnSaveQuery').show();
                $('#btnUpdateQuery').hide();
                $('#queryLastModified').text('');
            }
        },
        error: function() {
            $.jGrowl('Failed to delete query', { sticky: false, header: 'Error', theme: 'error' });
        }
    });
}

// TODO move to a global helper js
function copyToClipboard(id) {
	var range, selection, worked;

	var element = document.getElementById(id);

	if (document.body.createTextRange) {
		range = document.body.createTextRange();
		range.moveToElementText(element);
		range.select();
	} else if (window.getSelection) {
		selection = window.getSelection();        
		range = document.createRange();
		range.selectNodeContents(element);
		selection.removeAllRanges();
		selection.addRange(range);
	}
	
	try {
		document.execCommand('copy');
		$('#btnCopyEmbedCode').text('Copied!').css('color','green');
	}
	catch (err) {
		alert('unable to copy text');
	}
}

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

// copied and modified from settings.js
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

// copied and modified from settings.js
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

//==================================================================================================
//  AI ASSISTANT - Natural Language to SQL
//==================================================================================================

/**
 * Generate SQL from natural language input using OpenAI
 * @param {string} nlQuery - Natural language query
 * @param {string} database - Database ID
 */
function generateSQLFromNaturalLanguage(nlQuery, database) {
    if (!database) {
        $.jGrowl('Please select a database first', {
            sticky: false,
            header: 'Error',
            theme: 'error'
        });
        return;
    }

    // Show loading state
    var originalButtonText = $('#btnRunQuery').html();
    $('#btnRunQuery').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating SQL...');

    $.ajax({
        type: "POST",
        url: "/q/actions.php?action=ai_generateSQL",
        data: {
            natural_language_query: nlQuery,
            database_id: database
        },
        dataType: 'json',
        success: function(response) {
            $('#btnRunQuery').prop('disabled', false).html(originalButtonText);

            if (response.status === 'success' && response.data && response.data.sql) {
                // Set the generated SQL in the editor
                editor.setValue(response.data.sql, -1);

                // Switch back to SQL mode
                $('#btn-sql-mode').trigger('click');

                // Set query name to the natural language prompt for better UX
                // Truncate query name to 100 characters for display
                let displayName = truncateQueryName(nlQuery);
                $('#queryName').text(displayName).attr('title', nlQuery);
                $('#newQueryName').val(truncateQueryName(nlQuery));

                // Show success message
                $.jGrowl('SQL generated successfully! Review and click "Run Query" to execute.', {
                    sticky: false,
                    header: 'Success'
                });

                // Optional: show token usage info if available
                if (response.data.tokens_used) {
                    console.log('Tokens used:', response.data.tokens_used);
                }
            } else {
                $.jGrowl(response.message || 'Failed to generate SQL', {
                    sticky: false,
                    header: 'Error',
                    theme: 'error'
                });
            }
        },
        error: function(xhr) {
            $('#btnRunQuery').prop('disabled', false).html(originalButtonText);

            var errorMessage = 'Failed to generate SQL';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {
                // Use default error message
            }

            // Check if it's a configuration error (API key not set)
            if (xhr.status === 400 && errorMessage.includes('API key')) {
                $.jGrowl(errorMessage + ' <a href="/q/settings.php#ai_assistant" style="color: white; text-decoration: underline;">Configure in Settings</a>', {
                    sticky: true,
                    header: 'Configuration Required'
                });
            } else {
                $.jGrowl(errorMessage, {
                    sticky: false,
                    header: 'Error',
                    theme: 'error'
                });
            }
        }
    });
}