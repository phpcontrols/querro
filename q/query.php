<?php
// Initialization
include_once('../includes/load.php');

if (!isset($_databases)) {
    $_databases = array();

    // redirect to settings when no db
    header('Location: settings.php');
}

use phpCtrl\C_DataGrid;

include_once(__ROOT__ . "/includes/phpGrid/conf.php");

$cquery = ($_POST['cquery']) ?? false;
$queryId = ($_GET['id']) ?? false;
$shared = ($_POST['shared']) ?? false;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Querro - Query</title>
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

    <!-- Web Icons -->
    <link rel="stylesheet" href="/resources/css/web-icons.css">

    <!-- The main CSS file -->
    <link rel="stylesheet" href="/resources/css/styles.css">

    <link href="/resources/vendor/bootstrap-editable/css/bootstrap-editable.css" rel="stylesheet">
    <link href="/resources/vendor/jgrowl/jquery.jgrowl.css" rel="stylesheet">
    <link href="/resources/vendor/select2/select2.css" rel="stylesheet">
    <link href="/resources/vendor/select2/select2-bootstrap.css" rel="stylesheet">
    <link rel='stylesheet' href='/node_modules/nprogress/nprogress.css' />
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <style type='text/css'>
        #ace {
            position: relative;
            top: 0;
            right: 0;
            height: 350px;
            bottom: 0;
            left: 0;
        }

        .wrapper {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            float: left;
            width: 100%;
            text-align: center;
        }

        .dropdown-menu {
            padding: 3px !important;
        }

        /* centered modal */
        .modal {
            text-align: center;
        }

        @media screen and (min-width: 768px) {
            .modal:before {
                display: inline-block;
                vertical-align: middle;
                content: " ";
                height: 100%;
            }
        }

        .modal-dialog {
            display: inline-block;
            text-align: left;
            vertical-align: middle;
        }

        th.ui-th-column.jqgh_rn::after {
            content: '#';
        }

        /* Fixed left navigation, scrollable main content */
        .nav-left {
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .nav-left:hover {
            scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
        }

        .nav-left::-webkit-scrollbar {
            width: 8px;
        }

        .nav-left::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav-left::-webkit-scrollbar-thumb {
            background-color: transparent;
            border-radius: 4px;
        }

        .nav-left:hover::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.3);
        }

        .main-content {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
            background-color: #fff;
        }

        /* Style tabs in sidebar to match Settings page */
        .nav-left .nav-tabs {
            border-bottom: 1px solid #ddd;
            width: 100%;
        }

        .nav-left .nav-tabs > li {
            float: left;
            width: 50%;
            margin-bottom: -1px;
        }

        .nav-left .nav-tabs > li > a {
            margin-right: 0;
            line-height: 1.42857143;
            border: 1px solid transparent;
            border-radius: 0;
            color: #616161;
            text-align: center;
            padding: 10px 5px;
            font-size: 14px;
        }

        .nav-left .nav-tabs > li > a:hover {
            border-color: transparent transparent #616161 transparent;
            border-bottom-width: 2px;
            background-color: transparent;
        }

        .nav-left .nav-tabs > li.active > a,
        .nav-left .nav-tabs > li.active > a:hover,
        .nav-left .nav-tabs > li.active > a:focus {
            border: 1px solid transparent;
            border-bottom: 2px solid #2196F3;
            color: #424242;
            background-color: transparent;
            cursor: default;
        }

        .nav-left .tab-content {
            padding-top: 15px;
        }

        /* Query list - enable text wrapping and delete button styling */
        #queries_container .bootstrap-select .dropdown-menu li a {
            white-space: normal !important;
            min-height: auto !important;
            padding: 8px 12px !important;
        }

        #queries_container .bootstrap-select .dropdown-menu li a .query-name-text {
            white-space: normal !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
        }

        #queries_container .bootstrap-select .dropdown-menu .query-delete-btn:hover {
            color: #c9302c !important;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <?php
    // NAVBAR & SIDEBAR
    $current_page = basename(__FILE__, '.php');

    include('navbar-top.php');

    // use dummy array initially to get script_includeonce
    $dg = new C_DataGrid([[]]);
    $dg->enable_rownumbers(true);
    $dg->enable_export(true);
    $dg->display(false);
    $grid = $dg->get_display(false);
    $dg->display_script_includeonce();
    ?>



    <div id="main" class="with-navbar with-sidebar">

        <div class="panel clearfix">

            <form id="cie-form" role="form">

                <div id="databases_tables" class="tab-pane fade in active">

                    <div id="databases_template" class="container-fluid">

                        <div class="row">

                            <div class="col-md-2 col-sm-3 nav-left">

                                <!-- DATABASE DROPDOWN - ALWAYS VISIBLE AT TOP -->
                                <div id="databases_container" style="margin-bottom: 15px;">
                                    <h3 style="margin-top:3px">Database</h3>
                                    <select id="databases" name="databases" class="selectpicker">
                                        <?php
                                        foreach ($_databases as $key => $db)
                                            if ($db['active'] !== 0) {
                                                echo '<option value="' . $key . '"' . ((isset($db['active']) and $db['active'] === 0) ? '' : ' selected="selected"')  . '>' . ($db['label'] != '' ? $db['label'] : $db['name']) . '</option>';
                                            }
                                        ?>
                                    </select>
                                    <div id="databases_msg" class="selectpicker_msg" data-msg-1="No database configured, please add one."></div>
                                    <div id="database_disabled"></div>
                                </div>

                                <!-- TABS - BELOW DATABASE DROPDOWN -->
                                <div class="row">
                                    <ul class="nav nav-tabs">
                                        <li class="active"><a data-toggle="tab" href="#schemasTab">Schema</a></li>
                                        <li><a data-toggle="tab" href="#queriesTab">Queries</a></li>
                                    </ul>

                                    <div class="tab-content">
                                        <div id="queriesTab" class="tab-pane">
                                            <div id="queries_container">
                                                <h3 style="margin-top:3px">Queries</h3>
                                                <select id="queries" name="queries" class="selectpicker"></select>
                                                <div id="queries_msg" class="selectpicker_msg" data-msg-1="Saved query will appear here."></div>
                                            </div>
                                        </div>

                                        <div id="schemasTab" class="tab-pane fade in active">
                                            <div id="tables_container">
                                                <h3 style="margin-top:3px">Tables</h3>
                                                <select id="tables" name="tables" class="selectpicker"></select>
                                                <div id="tables_msg" class="selectpicker_msg" data-msg-1="Please select a database to see their tables." data-msg-2="No tables on this database."></div>
                                            </div>
                                            <div id="columns_container">
                                                <label>Columns</label>
                                                <select id="columns" name="columns" class="selectpicker"></select>
                                                <div id="columns_msg" class="selectpicker_msg" data-msg-1="" data-msg-2="No tables on this database."></div>
                                            </div>
                                        </div>

                                    </div>
                                </div><!-- nested row -->

                            </div>

                            <div class="col-md-10 col-sm-9 main-content">
                                <div class="">
                                    <!-- Unused legacy VisualQuery variables removed -->

                                    <!-- custom query -->
                                    <form method="post">
                                        <div class="panel panel-default" id="modal-custom-query"">
                                        <div class=" panel-heading ">
                                            <h4 class=" panel-title">
                                            <label id="queryName" for="btnRenameQuery"> <?= $newQuery ?? '' ?></label> <button type="button" id="btnRenameQuery" title="rename" class="btn" data-toggle="modal" data-target="#renameQueryModal"><i class="fa fa-pencil"></i></button>
                                            </h4>
                                        </div>
                                        <div class="panel-body">
                                            <!-- AI Assistant Mode Toggle -->
                                            <div style="margin-bottom: 10px;">
                                                <div class="btn-group" role="group">
                                                    <button type="button" id="btn-sql-mode" class="btn btn-sm btn-primary active">
                                                        <i class="fa fa-database"></i> SQL Mode
                                                    </button>
                                                    <button type="button" id="btn-nl-mode" class="btn btn-sm btn-default">
                                                        <i class="fa fa-comment"></i> Natural Language Mode
                                                    </button>
                                                </div>
                                                <span id="mode-indicator" class="label label-primary" style="margin-left: 10px;">SQL Mode</span>
                                            </div>

                                            <div id="ace"><?= $cquery ?></div>
                                        </div>
                                        <div class="panel-footer flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <button type="button" id="btnRunQuery" class="btn btn-success"><i class="fa fa-play"></i>
                                                    Run Query
                                                </button>
                                                <button type="button" id="btnSaveQuery" class="btn items-end btn-primary" style="display:block;"><i class="fa fa-save"></i>
                                                    Save Query
                                                </button>
                                                <button type="button" id="btnUpdateQuery" class="btn btn-primary" style="display:block;"><i class="fa fa-save"></i>
                                                    Update Query
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <div id="queryLastModified" class="text-lg font-normal"></div>
                                                <button type="button" id="btnShareView" class="btn btn-default" <?= $queryId ? '' : 'disabled'; ?> data-toggle="modal" data-target="#shareViewModal" style="display:none;"><i class="fa fa-share-alt"></i>
                                                    Share
                                                </button>
                                                <div class="shareViewBtns display-inline <?= ($shared) ? '' : 'hide'; ?>" style="display:none;">
                                                    <button type="button" id="btnStopShareView" class="btn btn-default">
                                                        <i class="fa fa-ban stop"></i>
                                                        Stop Share
                                                    </button>
                                                    <button type="button" id="btnEmbedCode" class="btn btn-default" data-toggle="modal" data-target="#shareViewModal">
                                                        <i class="fa fa-code"></i>
                                                        Embed
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="cquery" name="cquery" />
                                        <input type="hidden" id="queryId" name="queryId" value="<?= $queryId ?>" />
                                        <input type="hidden" id="shared" name="shared" value="<?= $shared ?>" />
                                    </form>
                                </div>

                                <div class="">
                                    <div id="datagrid">
                                        <?php
                                        if ($dg->db->dbType != 'local')
                                            echo $grid;
                                        ?>
                                    </div>

                                    <div class="clearfix"></div>
                                </div>


                            </div> <!-- col-md -->
                        </div>
                    </div>
               </div>
            </form>

        </div> <!-- panel -->

    </div> <!-- main -->

    <script>
        var selected_databases = <?php if (empty($_databases)) echo '{}';
                                    else echo json_encode($_databases); ?>;
    </script>

    <!-- Rename query modal -->
    <div class="modal fade" id="renameQueryModal" tabindex="-1" role="dialog" aria-labelledby="renameQueryModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="renameQueryModalCenterTitle">Rename Query</h4>
                </div>
                <div class="modal-body">
                    <label for="newQueryName">100 characters max</label>
                    <input id="newQueryName" type="text" class="form-control input-normal" maxlength="100" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="btnRenameQueryInModal" class="btn btn-primary">Rename</button>
                </div>
            </div>
        </div>
    </div>

    <!-- share view modal 
    <div class="modal fade" id="shareViewModal" tabindex="-1" role="dialog" aria-labelledby="shareViewMModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="shareViewMModalCenterTitle">
                    <i class="fa fa-code" style="padding-right: 30px"> Embed Code</i> 
                    <button id="btnCopyEmbedCode" onclick="copyToClipboard('embedcode')"><i class="fa fa-copy"></i></button>
                    <button><a id="sharedViewPreviewLink" href="#" target="_new"><i class="fa fa-eye"></i></a></button>
                </h4>
            </div>
            <div class="modal-body">
                <div class="modal-body well" id="embedcode">
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
            </div>
        </div>
    </div>
    -->

    <!-- JavaScript Includes -->
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
    <script src="/resources/js/query.js"></script>

    <script src="/resources/vendor/select2/select2.min.js"></script>
    <script src="/resources/vendor/bootstrap-editable/js/bootstrap-editable.min.js"></script>
    <script src="/resources/vendor/ace/src-min-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="/resources/vendor/jgrowl/jquery.jgrowl.js"></script>
    <script src="/resources/vendor/ace/src-min-noconflict/ext-language_tools.js"></script>
    <script src="/resources/vendor/ace/src-min-noconflict/mode-mysql.js" type="text/javascript" charset="utf-8"></script>
    <script src="/node_modules/clipboard/dist/clipboard.min.js"></script>

    <?= $cquery; ?>

    <!-- NProgress -->
    <script src='/node_modules/nprogress/nprogress.js'></script>

    <div class="modal load-overlay"></div>
    <script>
        $body = $("body");
        NProgress.configure({
            showSpinner: true,
            minimum: 0.3,
            trickleSpeed: 300
        });

        $(document).on({
            ajaxStart: function() {
                $("body").addClass("loading");
                NProgress.start();
            },
            ajaxStop: function() {
                NProgress.done();
                $body.removeClass("loading");
            }
        });
    </script>
</body>

</html>