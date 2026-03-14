<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$old = $input['old'] ?? '';
$new = $input['new'] ?? '';

if ($old !== getAdminPassword()) {
    echo json_encode(['success' => false, 'message' => '原密码错误']);
    exit;
}
if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'message' => '新密码至少6位']);
    exit;
}
setAdminPassword($new);
echo json_encode(['success' => true, 'message' => '密码已修改']);
?>