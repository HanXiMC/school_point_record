<?php
require_once 'config.php';

$uid = $_GET['uid'] ?? '';
if (empty($uid) || !file_exists(STUDENTS_PATH . $uid . '.json')) {
    http_response_code(404);
    echo json_encode(['error' => '学生不存在']);
    exit;
}
$data = json_decode(file_get_contents(STUDENTS_PATH . $uid . '.json'), true);
// 返回明细，并按时间倒序
$details = $data['details'];
usort($details, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$response = [
    'name' => $data['name'],
    'score' => $data['score'],
    'details' => $details
];
header('Content-Type: application/json');
echo json_encode($response);
?>