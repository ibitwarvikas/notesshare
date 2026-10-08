<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Please login to rate.']);
    exit;
}

$user = current_user();
$note_id = (int)($_POST['note_id'] ?? 0);
$rating  = (int)($_POST['rating'] ?? 0);

if ($note_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Invalid rating.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM notes WHERE id=?");
$stmt->execute([$note_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Note not found.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO ratings (user_id, note_id, rating) VALUES (?,?,?)
                       ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$stmt->execute([$user['id'], $note_id, $rating]);

$stmt = $pdo->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM ratings WHERE note_id=?");
$stmt->execute([$note_id]);
$data = $stmt->fetch();

echo json_encode([
    'success' => true,
    'avg' => round((float)$data['avg_r'], 1),
    'count' => (int)$data['cnt'],
]);