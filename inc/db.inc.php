<?php
/* GrooveVault — Database connection (XAMPP / localhost MySQL).
   Include this at the top of any page that needs the database:
       require_once __DIR__ . '/inc/db.inc.php';   // or '/db.inc.php' from within inc/
   It exposes a ready-to-use PDO instance as $pdo.

   XAMPP defaults: host=localhost, user=root, blank password. Change below
   if your MySQL credentials differ. */

const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'groovevault';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    DB_HOST,
    DB_PORT,
    DB_NAME,
    DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // In development show the reason; in production you'd log this instead.
    http_response_code(500);
    exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
