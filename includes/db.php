<?php
/**
 * includes/db.php
 * PDO database connection singleton.
 * Edit the constants below to match your XAMPP setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mkatteb');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production you'd log this; here we show clearly for dev
            die('<div style="font-family:monospace;color:red;padding:20px;">
                <strong>Database connection failed.</strong><br>
                Make sure XAMPP MySQL is running and the database exists.<br>
                Error: ' . htmlspecialchars($e->getMessage()) . '
            </div>');
        }
    }

    return $pdo;
}
