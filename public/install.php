<?php
/**
 * Querro Web Installer
 *
 * Single-file web installer for non-technical users.
 * DELETE THIS FILE after installation is complete.
 */

// Prevent direct access issues
if (php_sapi_name() === 'cli') {
    echo "This installer must be run from a web browser.\n";
    exit(1);
}

// Show ALL errors during installation
error_reporting(E_ALL);
ini_set('display_errors', '0'); // We capture them ourselves

// Project root is one level up from public/
define('PROJECT_ROOT', dirname(__DIR__));

// Collect PHP warnings/notices into $phpWarnings
$phpWarnings = [];
set_error_handler(function ($severity, $message, $file, $line) use (&$phpWarnings) {
    $types = [
        E_WARNING => 'Warning', E_NOTICE => 'Notice', E_DEPRECATED => 'Deprecated',
        E_USER_WARNING => 'Warning', E_USER_NOTICE => 'Notice', E_USER_DEPRECATED => 'Deprecated',
        E_STRICT => 'Strict',
    ];
    $label = $types[$severity] ?? 'Error';
    $phpWarnings[] = [
        'type' => $label,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ];
    return true; // Don't pass to default handler
});

session_start();

// If .env already exists, back it up with a timestamp and continue fresh install
if (file_exists(PROJECT_ROOT . '/.env')) {
    $timestamp = date('Ymd-His');
    rename(PROJECT_ROOT . '/.env', PROJECT_ROOT . '/.env-' . $timestamp);
}

// Current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$errorDetail = '';  // Technical detail (exception trace, SQL error code, etc.)
$errorHint = '';    // Human-readable fix suggestion
$errorBackStep = 0; // Which step to go back to for fixing

// Store form data in session across steps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['db_host'])) {
        $_SESSION['install_db'] = [
            'host' => $_POST['db_host'],
            'user' => $_POST['db_user'],
            'pass' => $_POST['db_pass'],
            'name' => $_POST['db_name'],
        ];
    }
    if (isset($_POST['app_url'])) {
        $_SESSION['install_app'] = [
            'url' => rtrim($_POST['app_url'], '/'),
            'env' => $_POST['app_env'],
        ];
    }
    if (isset($_POST['admin_email'])) {
        $_SESSION['install_admin'] = [
            'email' => $_POST['admin_email'],
            'username' => $_POST['admin_username'],
            'password' => $_POST['admin_password'],
        ];
    }
}

// ---- Step handlers (wrapped in try/catch) ----

try {

    // Step 2: Test DB connection
    if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
        $db = $_SESSION['install_db'];
        $conn = @mysqli_connect($db['host'], $db['user'], $db['pass']);
        if (!$conn) {
            $error = 'Database connection failed';
            $errorDetail = mysqli_connect_error() . ' (errno: ' . mysqli_connect_errno() . ')';
            $errorHint = 'Check that your database host, username, and password are correct. Make sure MySQL is running.';
            $errorBackStep = 2;
            $step = 2;
        } else {
            mysqli_close($conn);
            $step = 3;
        }
    }

    // Step 3: Run install.sql
    if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_schema'])) {
        $db = $_SESSION['install_db'];
        $conn = @mysqli_connect($db['host'], $db['user'], $db['pass']);
        if (!$conn) {
            $error = 'Database connection failed';
            $errorDetail = mysqli_connect_error() . ' (errno: ' . mysqli_connect_errno() . ')';
            $errorHint = 'The database credentials from the previous step no longer work. Go back and re-enter them.';
            $errorBackStep = 2;
            $step = 3;
        } else {
            // Create database if it doesn't exist
            $dbNameEscaped = mysqli_real_escape_string($conn, $db['name']);
            if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `{$dbNameEscaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $error = 'Failed to create database';
                $errorDetail = mysqli_error($conn) . ' (errno: ' . mysqli_errno($conn) . ')';
                $errorHint = "The MySQL user \"{$db['user']}\" may not have CREATE DATABASE privileges. Create the database manually or grant the privilege, then retry.";
                $errorBackStep = 2;
                $step = 3;
            } else {
                mysqli_select_db($conn, $db['name']);

                // Read and execute install.sql
                $sqlFile = PROJECT_ROOT . '/install.sql';
                if (!file_exists($sqlFile)) {
                    $error = 'install.sql not found';
                    $errorDetail = 'Expected path: ' . $sqlFile;
                    $errorHint = 'The install.sql file must exist in the project root directory. Re-download Querro or restore the file.';
                    $step = 3;
                } else {
                    $sql = file_get_contents($sqlFile);
                    $sql = preg_replace('/^--.*$/m', '', $sql);

                    if (mysqli_multi_query($conn, $sql)) {
                        do {
                            if ($result = mysqli_store_result($conn)) {
                                mysqli_free_result($result);
                            }
                        } while (mysqli_next_result($conn));
                    }

                    if (mysqli_errno($conn)) {
                        $mysqlErrno = mysqli_errno($conn);
                        $mysqlError = mysqli_error($conn);
                        $error = 'SQL import error';
                        $errorDetail = $mysqlError . ' (errno: ' . $mysqlErrno . ')';

                        if ($mysqlErrno === 1050) {
                            $errorHint = 'Some tables already exist. If this is a fresh install, drop the database and retry. If upgrading, this may be safe to ignore.';
                        } elseif ($mysqlErrno === 1062) {
                            $errorHint = 'Duplicate entry — the schema may have already been imported. If this is a fresh install, drop the database and retry.';
                        } else {
                            $errorHint = 'Review the SQL error above. You may need to check install.sql or your MySQL user permissions.';
                        }
                        $step = 3;
                    } else {
                        $step = 4;
                    }
                }
            }
            mysqli_close($conn);
        }
    }

    // Step 5: Create admin + write .env
    if ($step === 5 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_email'])) {
        $db = $_SESSION['install_db'];
        $app = $_SESSION['install_app'] ?? [];
        $admin = $_SESSION['install_admin'];

        if (strlen($admin['password']) < 6) {
            $error = 'Password too short';
            $errorDetail = 'Provided password is ' . strlen($admin['password']) . ' characters.';
            $errorHint = 'Enter a password with at least 6 characters.';
            $step = 5;
        } else {
            $conn = @mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
            if (!$conn) {
                $error = 'Database connection failed';
                $errorDetail = mysqli_connect_error() . ' (errno: ' . mysqli_connect_errno() . ')';
                $errorHint = 'Could not connect to the database. Go back to step 2 and verify your credentials.';
                $errorBackStep = 2;
                $step = 5;
            } else {
                $hashedPassword = password_hash($admin['password'], PASSWORD_BCRYPT, ['cost' => 10]);
                $email = mysqli_real_escape_string($conn, $admin['email']);
                $username = mysqli_real_escape_string($conn, $admin['username']);
                $roles = '["ROLE_ADMIN"]';

                $insertSql = "INSERT INTO `user` (`email`, `roles`, `password`, `account_id`, `username`, `active`)
                               VALUES ('{$email}', '{$roles}', '{$hashedPassword}', 1, '{$username}', 1)";

                if (!mysqli_query($conn, $insertSql)) {
                    $mysqlErrno = mysqli_errno($conn);
                    $mysqlError = mysqli_error($conn);
                    $error = 'Failed to create admin user';
                    $errorDetail = $mysqlError . ' (errno: ' . $mysqlErrno . ')';

                    if ($mysqlErrno === 1062) {
                        $errorHint = 'A user with this email already exists. Use a different email address, or drop the existing user from the database.';
                    } elseif ($mysqlErrno === 1452) {
                        $errorHint = 'Foreign key error — the default account (id=1) may not exist. Re-run the database setup (step 3).';
                        $errorBackStep = 3;
                    } else {
                        $errorHint = 'Check the SQL error above. The user table may be missing — go back and re-run database setup.';
                        $errorBackStep = 3;
                    }
                    $step = 5;
                } else {
                    $appSecret = bin2hex(random_bytes(16));
                    $appUrl = $app['url'] ?? detectAppUrl();
                    $appEnv = $app['env'] ?? 'prod';

                    $envContent = "# Querro Configuration (generated by installer)\n";
                    $envContent .= "DB_HOST={$db['host']}\n";
                    $envContent .= "DB_USER={$db['user']}\n";
                    $envContent .= "DB_PASS={$db['pass']}\n";
                    $envContent .= "DB_NAME={$db['name']}\n";
                    $envContent .= "\n";
                    $envContent .= "APP_URL={$appUrl}\n";
                    $envContent .= "APP_ENV={$appEnv}\n";
                    $envContent .= "APP_SECRET={$appSecret}\n";

                    if (file_put_contents(PROJECT_ROOT . '/.env', $envContent) === false) {
                        $error = 'Failed to write .env file';
                        $errorDetail = 'Path: ' . PROJECT_ROOT . '/.env';
                        $errorHint = 'The project root directory is not writable by the web server. Fix the file permissions (e.g. chmod 775) and retry.';
                        $step = 5;
                    } else {
                        // Clear Symfony cache so the new .env takes effect
                        $cacheCleared = false;
                        $cacheDirs = glob(PROJECT_ROOT . '/var/cache/*', GLOB_ONLYDIR);
                        if ($cacheDirs) {
                            foreach ($cacheDirs as $dir) {
                                deleteDirectory($dir);
                            }
                            $cacheCleared = true;
                        }
                        $step = 6;
                    }
                }
                mysqli_close($conn);
            }
        }
    }

} catch (\Throwable $e) {
    // Catch any uncaught exception or fatal error
    $error = 'Unexpected error: ' . $e->getMessage();
    $errorDetail = get_class($e) . " in {$e->getFile()}:{$e->getLine()}\n\nStack trace:\n{$e->getTraceAsString()}";
    $errorHint = 'An unexpected PHP error occurred. Check the details below and fix the underlying issue, then retry.';
}

function deleteDirectory(string $dir): void {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function detectAppUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

// ---- Requirements check data ----
function checkRequirements(): array {
    $checks = [];

    $checks[] = [
        'label' => 'PHP Version (8.1+)',
        'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'value' => PHP_VERSION,
    ];

    $requiredExts = ['mysqli', 'curl', 'json', 'ctype', 'iconv'];
    foreach ($requiredExts as $ext) {
        $checks[] = [
            'label' => "Extension: {$ext}",
            'ok' => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
        ];
    }

    $checks[] = [
        'label' => 'vendor/ directory',
        'ok' => is_dir(PROJECT_ROOT . '/vendor'),
        'value' => is_dir(PROJECT_ROOT . '/vendor') ? 'Found' : 'Missing',
    ];

    $varDir = PROJECT_ROOT . '/var';
    $varWritable = is_dir($varDir) && is_writable($varDir);
    if (!is_dir($varDir)) {
        @mkdir($varDir, 0775, true);
        $varWritable = is_dir($varDir) && is_writable($varDir);
    }
    $checks[] = [
        'label' => 'var/ directory writable',
        'ok' => $varWritable,
        'value' => $varWritable ? 'Writable' : 'Not writable',
    ];

    $rootWritable = is_writable(PROJECT_ROOT);
    $checks[] = [
        'label' => 'Project root writable (.env)',
        'ok' => $rootWritable,
        'value' => $rootWritable ? 'Writable' : 'Not writable',
    ];

    $checks[] = [
        'label' => 'install.sql file',
        'ok' => file_exists(PROJECT_ROOT . '/install.sql'),
        'value' => file_exists(PROJECT_ROOT . '/install.sql') ? 'Found' : 'Missing',
    ];

    return $checks;
}

$allPassed = true;
if ($step === 1) {
    $requirements = checkRequirements();
    foreach ($requirements as $req) {
        if (!$req['ok']) $allPassed = false;
    }
}

$detectedUrl = detectAppUrl();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Querro - Installer</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,600,700" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 400;
            margin: 0;
            min-height: 100vh;
        }
        .installer-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 15px;
        }
        .installer-card {
            width: 100%;
            max-width: 520px;
        }
        .brand {
            text-align: center;
            margin-bottom: 8px;
        }
        .brand-title {
            font-size: 40px;
            font-weight: 700;
            color: #333;
        }
        .brand-sub {
            font-size: 15px;
            color: #999;
        }
        .brand-desc {
            font-size: 14px;
            color: #636b6f;
            margin-top: 4px;
            margin-bottom: 20px;
        }
        .steps-bar {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 24px;
        }
        .steps-bar .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ddd;
        }
        .steps-bar .dot.active { background: #337ab7; }
        .steps-bar .dot.done { background: #5cb85c; }
        .card-box {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 28px 28px 20px;
        }
        .card-box h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px;
            color: #333;
        }
        .card-box .step-desc {
            font-size: 13px;
            color: #999;
            margin-bottom: 18px;
        }
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 4px;
            color: #555;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            border: 1px solid #ccc;
            border-radius: 4px;
            color: #333;
            transition: border-color 0.15s;
        }
        .form-control:focus {
            outline: none;
            border-color: #337ab7;
            box-shadow: 0 0 0 2px rgba(51,122,183,0.15);
        }
        select.form-control { height: 36px; }
        .btn {
            display: inline-block;
            padding: 9px 20px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-primary { background: #337ab7; color: #fff; }
        .btn-primary:hover { background: #286090; }
        .btn-success { background: #5cb85c; color: #fff; }
        .btn-success:hover { background: #449d44; }
        .btn-outline {
            background: #fff;
            color: #337ab7;
            border: 1px solid #337ab7;
        }
        .btn-outline:hover { background: #f0f6fb; }
        .btn-block {
            display: block;
            width: 100%;
            margin-top: 16px;
            text-align: center;
        }
        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }
        .btn-row .btn { flex: 1; text-align: center; }
        .alert {
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .alert-danger {
            background: #fdf2f2;
            color: #c0392b;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff8e1;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .alert-success {
            background: #f0fff0;
            color: #27ae60;
            border: 1px solid #c3e6cb;
        }
        .error-box {
            background: #fdf2f2;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .error-box .error-title {
            font-weight: 700;
            font-size: 15px;
            color: #c0392b;
            margin: 0 0 8px;
        }
        .error-box .error-detail {
            background: #fff5f5;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px 12px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 12px;
            color: #721c24;
            white-space: pre-wrap;
            word-break: break-word;
            margin: 8px 0;
            max-height: 200px;
            overflow-y: auto;
        }
        .error-box .error-hint {
            background: #fff8e1;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 13px;
            color: #856404;
            margin: 10px 0 0;
        }
        .error-box .error-hint strong {
            display: block;
            margin-bottom: 3px;
        }
        .warning-box {
            background: #fffbf0;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .warning-box .warning-title {
            font-weight: 700;
            font-size: 13px;
            color: #856404;
            margin: 0 0 6px;
        }
        .warning-box .warning-item {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 11px;
            color: #856404;
            padding: 4px 0;
            border-bottom: 1px solid #ffeeba;
        }
        .warning-box .warning-item:last-child { border-bottom: none; }
        .req-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .req-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .req-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .status-ok { color: #27ae60; }
        .status-fail { color: #c0392b; }
        .complete-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 12px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="installer-wrap">
    <div class="installer-card">
        <div class="brand">
            <div class="brand-title">Querro</div>
            <div class="brand-sub">by phpGrid</div>
            <div class="brand-desc">Installation Wizard</div>
        </div>

        <!-- Step indicators -->
        <div class="steps-bar">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"></div>
            <?php endfor; ?>
        </div>

        <div class="card-box">

        <?php // ---- Error display ---- ?>
        <?php if ($error): ?>
            <div class="error-box">
                <div class="error-title"><?= htmlspecialchars($error) ?></div>
                <?php if ($errorDetail): ?>
                    <div class="error-detail"><?= htmlspecialchars($errorDetail) ?></div>
                <?php endif; ?>
                <?php if ($errorHint): ?>
                    <div class="error-hint">
                        <strong>How to fix:</strong>
                        <?= htmlspecialchars($errorHint) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="btn-row">
                <?php if ($errorBackStep && $errorBackStep !== $step): ?>
                    <a href="?step=<?= $errorBackStep ?>" class="btn btn-outline">Go to Step <?= $errorBackStep ?></a>
                <?php endif; ?>
                <a href="?step=<?= $step ?>" class="btn btn-primary">All fixed? Retry</a>
            </div>

            <?php if ($step !== 2): // Don't hide the form on step 2 errors — user can just re-submit ?>
                <?php /* Skip rendering the step form below when showing error with navigation */ ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php // ---- PHP warnings display ---- ?>
        <?php if (!empty($phpWarnings)): ?>
            <div class="warning-box">
                <div class="warning-title">PHP Warnings (<?= count($phpWarnings) ?>)</div>
                <?php foreach ($phpWarnings as $w): ?>
                    <div class="warning-item">
                        [<?= htmlspecialchars($w['type']) ?>] <?= htmlspecialchars($w['message']) ?>
                        <br>in <?= htmlspecialchars(basename($w['file'])) ?>:<?= $w['line'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Step 1: Requirements Check -->
            <h2>Requirements Check</h2>
            <p class="step-desc">Verifying your server meets all requirements.</p>

            <table class="req-table">
            <?php foreach ($requirements as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['label']) ?></td>
                    <td class="<?= $req['ok'] ? 'status-ok' : 'status-fail' ?>">
                        <?= htmlspecialchars($req['value']) ?>
                        <?php if (!$req['ok']): ?>
                            <a href="https://www.google.com/search?q=<?= urlencode('How to fix ' . $req['label'] . ' ' . $req['value'] . ' PHP') ?>"
                               target="_blank" style="font-size:12px; margin-left:6px; color:#337ab7;">how to fix?</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>

            <?php if ($allPassed): ?>
                <a href="?step=2" class="btn btn-primary btn-block">Continue</a>
            <?php else: ?>
                <div class="alert alert-warning" style="margin-top:16px;">
                    Please fix the issues above before continuing.
                </div>
                <a href="?step=1" class="btn btn-primary btn-block">Re-check</a>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: Database Configuration -->
            <h2>Database Configuration</h2>
            <p class="step-desc">Enter your MySQL database credentials.</p>

            <form method="post" action="?step=2">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_db']['host'] ?? 'localhost') ?>" required>
                </div>
                <div class="form-group">
                    <label for="db_user">Database User</label>
                    <input type="text" id="db_user" name="db_user" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_db']['user'] ?? 'root') ?>" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_db']['pass'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_db']['name'] ?? 'querro') ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Test Connection &amp; Continue</button>
            </form>

        <?php elseif ($step === 3 && !$error): ?>
            <!-- Step 3: Database Setup -->
            <h2>Database Setup</h2>
            <p class="step-desc">Create database and import schema.</p>

            <p style="font-size:13px;">
                This will create the database <strong><?= htmlspecialchars($_SESSION['install_db']['name'] ?? 'querro') ?></strong>
                (if it doesn't exist) and import all required tables from <code>install.sql</code>.
            </p>

            <form method="post" action="?step=3">
                <input type="hidden" name="run_schema" value="1">
                <button type="submit" class="btn btn-primary btn-block">Run Database Setup</button>
            </form>

        <?php elseif ($step === 4): ?>
            <!-- Step 4: Application Config -->
            <h2>Application Configuration</h2>
            <p class="step-desc">Configure your application settings.</p>

            <form method="post" action="?step=5">
                <div class="form-group">
                    <label for="app_url">Application URL</label>
                    <input type="url" id="app_url" name="app_url" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_app']['url'] ?? $detectedUrl) ?>" required>
                </div>
                <div class="form-group">
                    <label for="app_env">Environment</label>
                    <select id="app_env" name="app_env" class="form-control">
                        <option value="prod" <?= ($_SESSION['install_app']['env'] ?? 'prod') === 'prod' ? 'selected' : '' ?>>Production</option>
                        <option value="dev" <?= ($_SESSION['install_app']['env'] ?? '') === 'dev' ? 'selected' : '' ?>>Development</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Continue</button>
            </form>

        <?php elseif ($step === 5 && !$error): ?>
            <!-- Step 5: Admin Account -->
            <h2>Admin Account</h2>
            <p class="step-desc">Create the administrator account.</p>

            <form method="post" action="?step=5">
                <div class="form-group">
                    <label for="admin_email">Email</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_admin']['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="admin_username">Username</label>
                    <input type="text" id="admin_username" name="admin_username" class="form-control"
                           value="<?= htmlspecialchars($_SESSION['install_admin']['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">Password</label>
                    <input type="password" id="admin_password" name="admin_password" class="form-control"
                           minlength="6" required>
                    <small style="color:#999;font-size:12px;">Minimum 6 characters</small>
                </div>
                <button type="submit" class="btn btn-success btn-block">Create Admin &amp; Finish Install</button>
            </form>

        <?php elseif ($step === 6): ?>
            <!-- Step 6: Complete -->
            <div class="complete-icon">&#10003;</div>
            <h2 style="text-align:center;">Installation Complete!</h2>
            <p class="step-desc" style="text-align:center;">Querro has been installed successfully.</p>

            <div class="alert alert-success">
                Your <code>.env</code> file has been created and the database is ready.
            </div>

            <div class="alert alert-warning">
                <strong>Security:</strong> Please delete <code>public/install.php</code> from your server now.
            </div>

            <a href="/login" class="btn btn-primary btn-block">Go to Login</a>

            <?php
            unset($_SESSION['install_db'], $_SESSION['install_app'], $_SESSION['install_admin']);
            ?>

        <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
