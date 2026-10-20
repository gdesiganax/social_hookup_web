<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_admin();

$verificationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($verificationId <= 0) {
    http_response_code(404);
    exit('Document not found.');
}

$stmt = db()->prepare('SELECT * FROM user_verifications WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $verificationId]);
$verification = $stmt->fetch();

if (!$verification) {
    http_response_code(404);
    exit('Document not found.');
}

$path = (string) $verification['government_id_path'];
$absolutePath = realpath(APP_ROOT . '/' . $path);
$storageRoot = realpath(APP_ROOT . '/storage/verifications');

if (!$absolutePath || !$storageRoot || strpos($absolutePath, $storageRoot) !== 0 || !is_file($absolutePath)) {
    http_response_code(404);
    exit('Document not found.');
}

$mime = (string) ($verification['government_id_mime'] ?: 'application/octet-stream');
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($verification['government_id_original_name'] ?: 'nin-document'));

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: inline; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;
