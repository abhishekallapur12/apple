<?php
/**
 * Shared start-up code for the demo pages.
 *
 * Every page includes this file first. It sets up error handling, the
 * session, the database connection, the login check, form tokens (CSRF
 * protection), password handling, the login-attempt limit and Smarty.
 */

$GLOBALS['demo_config'] = require __DIR__ . '/config.php';

function demo_config($key)
{
    return $GLOBALS['demo_config'][$key];
}

// ---------------------------------------------------------------------------
// Errors: log the details, show visitors only a short friendly message.
// ---------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', demo_config('debug') ? '1' : '0');
ini_set('log_errors', '1');

function demo_fail($publicMessage, $httpCode = 500)
{
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Problem</title></head><body>',
        '<p>', htmlspecialchars($publicMessage, ENT_QUOTES, 'UTF-8'), '</p>',
        '<p><a href="view.php">Back to the user list</a></p></body></html>';
    exit;
}

set_exception_handler(function ($e) {
    error_log('[smarty-demo] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    demo_fail(demo_config('debug') ? $e->getMessage() : 'Something went wrong. Please try again later.');
});

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // PHP 7.3+; ignored by older versions
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
session_start();

function demo_redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/** Keep a one-time message for the next page (for example "User added"). */
function demo_flash($message = null)
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $message = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
    unset($_SESSION['flash']);
    return $message;
}

// ---------------------------------------------------------------------------
// Database: one connection, prepared statements only.
// ---------------------------------------------------------------------------
function demo_db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    if (demo_config('db_user') === '') {
        error_log('[smarty-demo] DEMO_DB_USER / DEMO_DB_PASSWORD are not set.');
        demo_fail('The service is not set up yet. Please try again later.', 503);
    }
    $dsn = 'mysql:host=' . demo_config('db_host') . ';dbname=' . demo_config('db_name') . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, demo_config('db_user'), demo_config('db_password'), array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ));
    } catch (PDOException $e) {
        error_log('[smarty-demo] Database connection failed: ' . $e->getMessage());
        demo_fail('The service is not available, please try again later.', 503);
    }
    return $pdo;
}

/**
 * Run one database command. User input always goes in $params, never into $sql.
 *
 * @return PDOStatement
 */
function demo_query($sql, array $params = array())
{
    $stmt = demo_db()->prepare($sql);
    $position = 1;
    foreach ($params as $value) {
        $stmt->bindValue($position++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt;
}

// ---------------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------------
function demo_current_user()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return array('id' => (int)$_SESSION['user_id'], 'name' => (string)$_SESSION['uname']);
}

/** Send visitors who are not logged in (or whose account was deleted) to the login page. */
function demo_require_login()
{
    $user = demo_current_user();
    if ($user !== null) {
        $stillExists = demo_query('SELECT COUNT(*) FROM user WHERE id = ?', array($user['id']))->fetchColumn();
        if ((int)$stillExists > 0) {
            return $user;
        }
        demo_log_out();
    }
    demo_redirect('login.php');
}

function demo_log_in($id, $name)
{
    // A fresh session number at login stops session fixation.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$id;
    $_SESSION['uname'] = (string)$name;
    unset($_SESSION['csrf_token']);
}

function demo_log_out()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------------------
// Form tokens (CSRF protection): every form that changes data sends one.
// ---------------------------------------------------------------------------
function demo_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function demo_check_csrf()
{
    $sent = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        demo_fail('This form has expired. Please go back, reload the page and try again.', 400);
    }
}

// ---------------------------------------------------------------------------
// Passwords: one method for add, edit and login.
// ---------------------------------------------------------------------------
function demo_hash_password($plain)
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

/**
 * Check a password. Accounts created before this fix were stored with MD5;
 * they are accepted once and $needsRehash is set so the caller can upgrade them.
 */
function demo_check_password($plain, $stored, &$needsRehash)
{
    $needsRehash = false;
    $stored = (string)$stored;
    if (preg_match('/^[0-9a-f]{32}$/i', $stored)) {
        $needsRehash = true;
        return hash_equals(strtolower($stored), md5($plain));
    }
    if (password_verify($plain, $stored)) {
        $needsRehash = password_needs_rehash($stored, PASSWORD_DEFAULT);
        return true;
    }
    return false;
}

/** @return string[] list of problems with the form values (empty when all is fine) */
function demo_validate_user($name, $age, $password, $passwordRequired)
{
    $errors = array();
    if ($name === '') {
        $errors[] = 'Please enter a name.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'The name may be at most 100 characters long.';
    }
    if (!preg_match('/^\d{1,3}$/', $age) || (int)$age > 150) {
        $errors[] = 'Age must be a whole number between 0 and 150.';
    }
    if ($passwordRequired || $password !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'The password must be at least 8 characters long.';
        }
    }
    return $errors;
}

// ---------------------------------------------------------------------------
// Login-attempt limit, per name and per visitor address.
// ---------------------------------------------------------------------------
function demo_login_attempt_files($name)
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smarty-demo-login';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    return array(
        // An address may be shared by many people (an office), so it gets a higher limit.
        $dir . DIRECTORY_SEPARATOR . 'ip-' . hash('sha256', $ip)                => demo_config('max_login_failures') * 4,
        $dir . DIRECTORY_SEPARATOR . 'name-' . hash('sha256', strtolower($name)) => demo_config('max_login_failures'),
    );
}

function demo_read_attempts($file)
{
    $data = is_file($file) ? json_decode((string)@file_get_contents($file), true) : null;
    if (!is_array($data) || !isset($data['count'], $data['first'])
        || time() - (int)$data['first'] >= demo_config('login_lock_seconds')) {
        return array('count' => 0, 'first' => time());
    }
    return $data;
}

function demo_login_blocked($name)
{
    foreach (demo_login_attempt_files($name) as $file => $limit) {
        $data = demo_read_attempts($file);
        if ($data['count'] >= $limit) {
            return true;
        }
    }
    return false;
}

function demo_record_login_failure($name)
{
    foreach (demo_login_attempt_files($name) as $file => $limit) {
        $data = demo_read_attempts($file);
        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
}

function demo_clear_login_failures($name)
{
    foreach (array_keys(demo_login_attempt_files($name)) as $file) {
        if (strpos($file, 'name-') !== false) {
            @unlink($file);
        }
    }
}

// ---------------------------------------------------------------------------
// Smarty: one shared copy of the engine (../libs), automatic escaping on.
// ---------------------------------------------------------------------------
require_once dirname(__DIR__) . '/libs/Smarty.class.php';

function demo_smarty()
{
    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . '/templates/');
    $smarty->setCompileDir(__DIR__ . '/templates_c/');
    $smarty->setCacheDir(__DIR__ . '/cache/');
    $smarty->setConfigDir(__DIR__ . '/configs/');
    // Make every value harmless before it is shown (stops XSS).
    $smarty->escape_html = true;
    $smarty->assign('current_user', demo_current_user());
    $smarty->assign('csrf_token', demo_csrf_token());
    $smarty->assign('flash', demo_flash());
    return $smarty;
}
