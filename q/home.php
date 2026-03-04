<?php
// Initialization
include_once('../includes/load.php');
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>CSV IMPORT & EXPORT</title>
	<meta name="description" content="CSV Import & Export">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google web fonts -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500" rel="stylesheet">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/node_modules/bootstrap/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="/node_modules/@fortawesome/fontawesome-free/css/all.css">

    <!-- Web Icons -->
	<link rel="stylesheet" href="/resources/css/web-icons.css">

    <!-- The main CSS file -->
	<link rel="stylesheet" href="/resources/css/styles.css">

</head>

<body>
`
    <?php
    // NAVBAR & SIDEBAR
    $current_page = basename(__FILE__, '.php');
    
    include(__ROOT__ .'/q/navbar-top.php');
    ?>

	<div id="main" class="with-navbar with-sidebar">

        <h2>BeyondDash, InnerDash, DataBee</h2>
    
        <table class="table">
            <tr>
                <td><a href="https://www.dataproviders.io/">DataProviders.io</a></td>
                <td>Platform that provides cloud data tools to manage, process, analyze, visualize and share information</td>
            </tr>
            <tr>
                <td><a href="https://www.seekwell.io/">SeekWell.io</a></td>
                <td>SQL in the apps you already use. Get your SQL data in the places you need it: Google Sheets, Excel, Slack, and email.</td>
            </tr>
            <tr>
                <td><a href="https://www.draxed.com/">Draxed.com</a></td>
                <td>CREATE CHARTS FROM YOUR DATA. ANSWER QUESTIONS ABOUT YOUR DATA</td>
            </tr>
            <tr>
                <td><a href="https://internal.io/">Internal.io</a></td>
                <td>Free your engineers from internal tools. Allow anyone in your company to build approval queues on top of your databases, APIs, and business apps.</td>
            </tr>
            <tr>
                <td><a href="https://www.youtube.com/watch?v=6wZmYMWKLkY&ab_channel=krueggen">DabbleDB</a></td>
                <td>Create database applications inside a web browser</td>
            </tr>
            <tr>
                <td><a href="https://www.metabase.com/">Metabase.com</a></td>
                <td>Have questions about your data? Connect to your data and get it in front of your team and explore without the SQL barrier</td>
            </tr>
            <tr>
                <td><a href="https://retool.com/">Retool.com</a></td>
                <td>Build internal tools, remarkably fast. Stop wrestling with UI libraries, hacking together data sources, and figuring out access controls. Start shipping apps that move your business forward.</td>
            </tr>
            <tr>
                <td><a href="https://airbnb.io/projects/superset/">Superset</a></td>
                <td>Apache Superset (incubating) is a modern, enterprise-ready business intelligence web application</td>
            </tr>
        </table>
    
    </div>

    <!-- JavaScript Includes -->

    <!-- jQuery -->
    <script src="/node_modules/jquery/dist/jquery.min.js"></script>
    <script src="/node_modules/jquery-migrate/dist/jquery-migrate.min.js"></script>

    <!-- jQuery UI -->
    <script src="/node_modules/jquery-ui-dist/jquery-ui.min.js"></script>

    <!-- Underscore -->
    <script src="/node_modules/underscore/underscore-min.js"></script>

    <!-- Bootstrap -->
    <script src="/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

</body>

</html>