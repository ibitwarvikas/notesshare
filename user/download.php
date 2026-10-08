<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in()) {
    flash('error', 'Please sign in to download.');
    redirect('../index.php');
}
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM notes WHERE id=?");
$st->execute([$id]);
$note = $st->fetch();
if (!$note) { http_response_code(404); die('Note not found.'); }

$path = UPLOAD_DIR . 'notes/' . $note['filename'];
if (!file_exists($path)) { http_response_code(404); die('File missing.'); }

$pdo->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id=?")->execute([$id]);

header('Content-Description: File Transfer');
header('Content-Type: ' . ($note['filetype'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($note['original_name']) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache');
readfile($path);
exit;