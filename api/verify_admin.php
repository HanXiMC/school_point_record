<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$pwd = $input['password'] ?? '';
$correct = getAdminPassword();

if ($pwd === $correct) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>