<?php
declare(strict_types=1);

session_start();

include_once(__DIR__ ."/../includes/phpGrid/conf.php");
include_once(__DIR__ ."/../config/databases.php");

$dbStr = $_POST['db'] ?? die('No database connection information.');
$theTable = $_POST['tableName'] ?? die('Select a table or run custom query.');
$cquery = $_POST['cquery'] ?? "select * from $theTable";

$dbName = explode("@", $dbStr)[0];
$server = explode(":", explode("@", $dbStr)[1])[0];
$port = explode(":", explode("@", $dbStr)[1])[1];

$_SESSION['AccountId'] ?? die('Not authenticated.');
?> 

<div class="grid-toolbar">
	<h4>Data &nbsp; <i class="fa fa-refresh" id="btnRefreshPreview" title="Manual refresh"></i></h4>
</div>

<!-- Hidden form for POST to iframe -->
<form id="previewForm" method="POST" target="iframePreview" style="display: none;">
    <input type="hidden" name="db" id="preview_db" value="<?= htmlspecialchars($dbStr, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="table" id="preview_table" value="<?= htmlspecialchars($theTable, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="cquery" id="preview_cquery" value="<?= htmlspecialchars($cquery, ENT_QUOTES, 'UTF-8') ?>">
</form>

<iframe id="iframePreview" name="iframePreview"
    src="about:blank"
    allowtransparency="true"
    scrolling="no"
    onload="this.style.height = this.contentWindow.document.body.scrollHeight + 'px';">
</iframe>


<script>
// Auto-submit form to load preview on page load
$(document).ready(function() {
	// Submit form to iframe after a short delay to ensure iframe is ready
	setTimeout(function() {
		submitPreviewForm();
	}, 100);
});

// Function to submit preview form
function submitPreviewForm() {
	var form = document.getElementById('previewForm');
	form.action = 'preview.php';
	form.submit();
}

// Manual refresh button handler
$('#btnRefreshPreview').on('click', function() {
	submitPreviewForm();
});
</script>