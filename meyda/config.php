<?php
// config.php - edit these values for your host
// define("DB_HOST", "mysql-meyda.alwaysdata.net"); // Database host address (force TCP/IP connection)
// define("DB_NAME", "meyda_collection");
// define("DB_USER", "meyda"); // set to the DB user you create on your server
// define("DB_PASS", "kraccbacc"); // Change this to a strong password
// define("DEFAULT_USER_ID", 1); // id_user used for transactions (admin). Ensure it exists.

define("DB_HOST", "mysql-meyda.alwaysdata.net"); // Database host address (force TCP/IP connection)
define("DB_NAME", "meyda_collection");
define("DB_USER", "meyda"); // set to the DB user you create on your server
define("DB_PASS", "kraccbacc"); // Change this to a strong password
define("DEFAULT_USER_ID", 1); // id_user used for transactions (admin). Ensure it exists.

// Detect Base URL for assets
if (!defined("BASE_URL")) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $proj_root = str_replace('\\', '/', __DIR__);
    
    // If the folder we are in (__DIR__) is the same as DOCUMENT_ROOT, 
    // it means the site is pointed directly here.
    if (trim($doc_root, '/') === trim($proj_root, '/')) {
        $base_path = '/';
    } else {
        $base_path = str_replace($doc_root, '', $proj_root);
        // Ensure it starts and ends with /
        $base_path = '/' . trim($base_path, '/') . '/';
        if ($base_path === '//') $base_path = '/';
    }
    
    define("BASE_URL", $protocol . $host . $base_path);
}

/**
 * Helper to generate absolute asset URLs
 */
function asset($path) {
    return BASE_URL . ltrim($path, '/');
}

// Generate a secure key if not already set
if (!defined("MEYDA_ADMIN_KEY")) {
    $admin_key = getenv("MEYDA_ADMIN_KEY") ?: bin2hex(random_bytes(32));
    putenv("MEYDA_ADMIN_KEY=$admin_key");
    define("MEYDA_ADMIN_KEY", $admin_key);
}

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Note: avoid using PDO::MYSQL_ATTR_INIT_COMMAND to prevent deprecation on PHP 8.5+
];

function getPDO()
{
    static $pdo = null;
    global $pdoOptions;
    if ($pdo === null) {
        // Direct approach: use defined constants as-is
        $host = DB_HOST;
        $port = null;

        // Extract port from host if present
        $host_parts = explode(":", $host, 2);
        if (count($host_parts) === 2 && is_numeric($host_parts[1])) {
            $host = $host_parts[0];
            $port = (int) $host_parts[1];
        }

        // Build DSN with optional port
        $dsn =
            "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        if ($port) {
            $dsn .= ";port=" . $port;
        }

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    }
    return $pdo;
}
/**
 * Shorthand for htmlspecialchars
 */
function h($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
