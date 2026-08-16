<?php
/**
 * Database connection
 * Default XAMPP settings — change if your MySQL user/password differ.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'khajatime');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error() .
        '<br>Make sure XAMPP MySQL is running and you imported database/khajatime.sql');
}

mysqli_set_charset($conn, 'utf8mb4');
