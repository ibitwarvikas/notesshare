<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'noteshare');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', 'http://localhost/noteshare');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

$ALLOWED_NOTE_TYPES = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt'  => 'text/plain',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
];

foreach ([UPLOAD_DIR, UPLOAD_DIR . 'notes/', UPLOAD_DIR . 'avatars/'] as $d) {
    if (!is_dir($d)) @mkdir($d, 0775, true);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ); 
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

