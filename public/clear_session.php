<?php
session_start();

// Clear database cache
unset($_SESSION['_databases_cache']);
unset($_SESSION['_databases_cache_account']);

echo "✓ Session cache cleared successfully!<br><br>";
echo "<a href='/q/query.php'>← Return to query page</a>";