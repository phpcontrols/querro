<?php

// Initialization
include_once('../includes/load.php');

if (!isset($_databases))
    $_databases = array();
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Querro - Settings</title>
	<meta name="description" content="Settings">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google web fonts -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500" rel="stylesheet">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/node_modules/bootstrap/dist/css/bootstrap.min.css">

    <!-- Toggles -->
	<link rel="stylesheet" href="/node_modules/jquery-toggles/css/toggles.css">
	<link rel="stylesheet" href="/node_modules/jquery-toggles/css/themes/toggles-light.css">

    <!-- Bootstrap Select -->
    <link rel="stylesheet" href="/node_modules/bootstrap-select/dist/css/bootstrap-select.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="/node_modules/@fortawesome/fontawesome-free/css/all.css">
    <script src="/node_modules/@fortawesome/fontawesome-free/js/all.js"></script>

    <!-- Web Icons -->
	<link rel="stylesheet" href="/resources/css/web-icons.css">

    <!-- The main CSS file -->
	<link rel="stylesheet" href="/resources/css/styles.css">

    <link rel='stylesheet' href='/node_modules/nprogress/nprogress.css'/>
</head>
<body>

    <?php
    // NAVBAR & SIDEBAR
    $current_page = basename(__FILE__, '.php');
    
    include('navbar-top.php');
    ?>

	<div id="main" class="with-navbar with-sidebar">

        <div class="panel clearfix">
            <div class="panel-body">

                <div class="alert alert-info alert-dismissible" role="alert" style="margin: 3px;padding: 10px;font-size: 18px;">
                    <strong><i class="fa fa-exclamation-circle icon-float-left"></i> MySQL is currently supported. All tables require a primary key. You may need to whitelist IP (<?=  gethostbyname($_SERVER['SERVER_NAME']); ?>) for your connection to work.</strong>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="padding-right:20px">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="cie-form" role="form">

                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#databases_tables">Databases</a></li>
                        <!-- <li><a data-toggle="tab" href="#import">Import</a></libxml_clear_errors> -->
                        <!-- <li><a data-toggle="tab" href="#export">Export</a></li> -->
                        <li><a data-toggle="tab" href="#ai_assistant">AI Assistant</a></li>
                        <li><a data-toggle="tab" href="#info">Info</a></li>
                    </ul>

                    <div class="tab-content">

                        <div id="databases_tables" class="tab-pane fade in active">

                            <h2>
                                Databases
                                <small>Define the settings of the databases</small>
                            </h2>

                            <div id="databases_template">
                                <div class="row">
                                    <div class="col-md-6">

                                        <div id="databases_container">
                                            
                                            <select id="databases" name="databases" class="selectpicker" multiple="multiple">
                                                <?php
                                                foreach ($_databases as $key => $db)
                                                    echo '<option value="' . $key . '"' . ((isset($db['active']) and $db['active'] === 0) ? '' : ' selected="selected"')  . '>' . ($db['label'] != '' ? $db['label'] : $db['name']) . '</option>';
                                                ?>
                                            </select>
                                            <div id="databases_msg" class="selectpicker_msg" data-msg-1="No database configured, please add one."></div>
                                            <div id="database_disabled"></div>
                                        </div>

                                        <button id="btn-databases-add" type="button" class="btn btn-sm btn-outline-primary">Add</button>
                                        <button id="btn-databases-edit" type="button" class="btn btn-sm btn-outline-primary" style="display: none;">Edit</button>
                                        <button id="btn-databases-delete" type="button" class="btn btn-sm btn-outline-primary" style="display: none;">Delete</button>

                                        <div id="confirm-delete" class="modal modal-danger fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div class="modal-title">
                                                            DELETE DATABASE
                                                        </div>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <h4 class="alert-heading">
                                                                Please note that the database itself will NOT be erased. Instead you just remove the connection settings.
                                                                <br><br>
                                                                Be sure to click SAVE button to save the change.

                                                            </h4>

                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                        <button id="btn-databases-confirm-delete" type="button" class="btn btn-danger btn-ok">Delete</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div id="database_info" style="display: none;">
                                            <div class="form-group">
                                                <label>Label</label>
                                                <input type="text" id="db_label" name="db_label" value="" class="form-control" />
                                                <small class="help-block">A text that you can quickly identify the database (can be the same as the DB name)</small>
                                            </div>
                                            <div class="form-group">
                                                <label>Database Type</label>
                                                <select id="db_type" name="db_type" class="form-control">
                                                    <option value="mysql">MySQL</option>
                                                </select>
                                                <small class="help-block">MySQL is the supported database type.</small>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-sm-8">
                                                    <label>Server</label>
                                                    <input type="text" id="db_server" name="db_server" value="" class="form-control" />
                                                </div>
                                                <div class="form-group col-sm-4">
                                                    <label>Port</label>
                                                    <input type="text" id="db_port" name="db_port" value="3306" placeholder="e.g. 3306 is MySQL default port" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Database Name</label>
                                                <input type="text" id="db_name" name="db_name" value="" class="form-control" />
                                            </div>
                                            <div class="form-group">
                                                <label>Username</label>
                                                <input type="text" id="db_username" name="db_username" value="" class="form-control" />
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" id="db_password" name="db_password" value="" class="form-control" />
                                            </div>

                                            <!-- Hidden fields -->
                                            <input type="hidden" id="db_charset" name="db_charset" value="" />

                                            <button id="btn-databases-ok" type="button" class="btn btn-sm btn-outline-primary push-right">Ok</button>
                                            <button id="btn-databases-cancel" type="button" class="btn btn-sm btn-default push-right">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="clearfix" style="margin-bottom: 40px;"></div>

                            <h2>
                                Tables & columns
                                <small>Select which tables and corresponding columns you want to use</small>
                            </h2>

                            <div class="row">
                                <div id="tables_container" class="form-group col-sm-6">
                                    <label>Tables</label>
                                    <select  id="tables" name="tables" class="selectpicker" multiple="multiple"></select>
                                    <div id="tables_msg" class="selectpicker_msg" data-msg-1="Please select a database to see their tables." data-msg-2="No tables on this database."></div>
                                    <button id="btn-tables-select-all" type="button" class="btn btn-sm btn-outline-primary" style="display: none;">Select all</button>
                                    <button id="btn-tables-deselect-all" type="button" class="btn btn-sm btn-outline-primary" style="display: none;">Deselect all</button>
                                </div>

                                <div id="columns_container" class="form-group col-sm-6">
                                    <label>Columns</label>
                                    <select  id="columns" name="columns" class="selectpicker" multiple="multiple"></select>
                                    <div id="columns_msg" class="selectpicker_msg" data-msg-1="Please select a table to see their columns." data-msg-2="No tables on this database."></div>
                                    <button id="btn-columns-select-all" type="button" class="btn btn-sm btn-outline-primary" style="display: none;">Select all</button>
                                    <button id="btn-columns-deselect-all" type="button" class="btn btn-sm btn-outline-primary" style="display: none;">Deselect all</button>
                                </div>

                            </div>

                            <div id="btn-nav">
                                <button id="btn-save" type="button" class="btn btn-primary btn-info-full"> Save Settings </button>
                            </div>
                            
                        </div>

                        <div id="ai_assistant" class="tab-pane fade">

                            <h2>
                                OpenAI Settings
                                <small>Configure Natural Language to SQL conversion</small>
                            </h2>

                            <div class="row">
                                <div class="col-md-8">
                                    <!-- API Key Form -->
                                    <div class="form-group">
                                        <label>OpenAI API Key</label>
                                        <div class="input-group">
                                            <input type="password" id="openai_api_key" class="form-control" placeholder="sk-..." />
                                            <span class="input-group-btn">
                                                <button id="btn-toggle-api-key" class="btn btn-default" type="button">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </span>
                                        </div>
                                        <small class="help-block">
                                            Your API key is encrypted and stored securely.
                                            <a href="https://platform.openai.com/api-keys" target="_blank">Get API Key</a>
                                        </small>
                                    </div>

                                    <!-- Model Selection -->
                                    <div class="form-group">
                                        <label>AI Model</label>
                                        <select id="openai_model" class="form-control">
                                            <option value="gpt-4o">GPT-4o (Recommended - 128K context)</option>
                                            <option value="gpt-4o-mini">GPT-4o Mini (Faster, cheaper)</option>
                                            <option value="gpt-4-turbo">GPT-4 Turbo (128K context)</option>
                                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo (16K context, legacy)</option>
                                        </select>
                                        <small class="help-block">
                                            Larger models handle complex schemas better but cost more.
                                            <a href="https://openai.com/api/pricing/" target="_blank">View Pricing</a>
                                        </small>
                                    </div>

                                    <!-- Test Connection -->
                                    <div class="form-group">
                                        <button id="btn-test-ai-connection" type="button" class="btn btn-primary">
                                            <i class="fa fa-check-circle"></i> Test Connection
                                        </button>
                                        <button id="btn-save-ai-settings" type="button" class="btn btn-success">
                                            <i class="fa fa-save"></i> Save Settings
                                        </button>
                                    </div>

                                    <!-- Status Display -->
                                    <div id="ai-status-display" class="alert" style="display:none;">
                                        <span id="ai-status-message"></span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="alert alert-info">
                                        <h4><i class="fa fa-info-circle"></i> How to use</h4>
                                        <ol>
                                            <li>Get your OpenAI API key from the link above</li>
                                            <li>Paste it here and select your preferred model</li>
                                            <li>Click "Test Connection" to verify</li>
                                            <li>Save your settings</li>
                                            <li>Go to Query page and toggle to "Natural Language Mode"</li>
                                        </ol>
                                        <p><strong>Privacy:</strong> Your API key is encrypted and only accessible by you.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Usage Statistics -->
                            <div class="row" style="margin-top: 30px;">
                                <div class="col-md-12">
                                    <h3>Recent Usage</h3>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Natural Language Query</th>
                                                <th>Model</th>
                                                <th>Success</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ai-usage-tbody">
                                            <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>


                        <div id="info" class="tab-pane fade">
                            <table id="ct" class="table">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>upload_max_filesize</td>
                                        <td><?php echo UPLOAD_MAX_FILESIZE . ' <small>(' . return_bytes(UPLOAD_MAX_FILESIZE) . ' bytes)</small>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>max_file_uploads</td>
                                        <td><?php echo MAX_FILE_UPLOADS; ?></td>
                                    </tr>
                                    <tr>
                                        <td>post_max_size</td>
                                        <td><?php echo POST_MAX_SIZE . ' <small>(' . return_bytes(POST_MAX_SIZE) . ' bytes)</small>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>memory_limit</td>
                                        <td><?php echo MEMORY_LIMIT . ' <small>(' . return_bytes(MEMORY_LIMIT) . ' bytes)</small>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>max_execution_time</td>
                                        <td><?php echo MAX_EXECUTION_TIME . ' s'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>max_input_time</td>
                                        <td><?php echo MAX_INPUT_TIME . ' s'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>allow_url_fopen</td>
                                        <td><?php echo (ALLOW_URL_FOPEN ? 'Yes' : 'No'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>curl</td>
                                        <td><?php echo (CURL_INIT ? 'Yes' : 'No'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                    </div>

                    <div id="alert-msg" class="alert alert-warning" style="display: none;">
                        <strong>Warning!</strong> <span>Indicates a warning that might need attention.</span>
                    </div>

                    <div id="modal-msg" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header label-success text-white">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                    <div class="modal-title">
                                        SUCCESS
                                    </div>
                                </div>
                                <div class="modal-body">
                                    Settings saved successfully!
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </form>

            </div>    <!-- panel-body -->
        </div>    <!-- panel -->

    </div>

    <script>
    var selected_databases = <?php if (empty($_databases)) echo '{}'; else echo json_encode($_databases); ?>;
    </script>

    <!-- JavaScript Includes -->

    <!-- jQuery -->
    <!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>-->
    <script src="/node_modules/jquery/dist/jquery.min.js"></script>
    <script src="/node_modules/jquery-migrate/dist/jquery-migrate.min.js"></script>

    <!-- Underscore -->
    <script src="/node_modules/underscore/underscore-min.js"></script>

    <!-- Popper -->
    <script src="/node_modules/@popperjs/core/dist/umd/popper.min.js"></script>

    <!-- Bootstrap -->
    <script src="/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Bootstrap Select -->
    <script src="/node_modules/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

    <!-- Toggles -->
    <script src="/node_modules/jquery-toggles/toggles.min.js"></script>

    <!-- Our main JS file -->
    <script src="/resources/js/settings.js"></script>

    <!-- NProgress -->
    <script src='/node_modules/nprogress/nprogress.js'></script>

    <!-- Toast message -->
     <script src='/node_modules/notifyjs-browser/dist/notify.js'></script>

    <div class="modal load-overlay"></div>
    <script>
    $body = $("body");
    NProgress.configure({ showSpinner: true, minimum: 0.3, trickleSpeed: 300 });

    $(document).on({
        ajaxStart:  function() { 
            $("body").addClass("loading"); NProgress.start(); 

            if (!$('#btn-save').hasClass('disabled')) {
                $('#btn-save').addClass('disabled');
            }
        },
        ajaxStop:   function() { 
            NProgress.done();$body.removeClass("loading");  

            $('#btn-save').removeClass('disabled'); 
        }    
    });

    // preload loader image to be cached by browser due to its file size (30kb) 
    $.preloadImages = function() {
      for (var i = 0; i < arguments.length; i++) {
        $("<img />").attr("src", arguments[i]);
      }
    }
    $.preloadImages("/resources/images/zen_spinner_circle_o.gif");

    $(document).ready(function($){ 
        $(document).ajaxComplete(function( event, xhr, settings ) {
            var notifyOptions = 
                {
                    autoHide: true, 
                    autoHideDelay: 8000
                };
            
            if (settings.url.split("?")[0].indexOf("home.php") > 0) {
                if (xhr.responseText.indexOf("error")>=0) {
                    $.notify(xhr.responseJSON?.msg ?? xhr.responseText, notifyOptions);
                }
                // }else{
                //     $.notify("Saved successfully!", "success");
                // }
            }
        });
    });
    </script>

</body>
</html>