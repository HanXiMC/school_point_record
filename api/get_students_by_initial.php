<?php
require_once 'config.php';

$initial = $_GET['initial'] ?? '';
if (strlen($initial) !== 1) {
    echo json_encode([]);
    exit;
}
$initial = strtoupper($initial);
$students = getAllStudents();
$result = [];
foreach ($students as $uid => $info) {
    if ($info['initial'] === $initial) {
        $result[] = [
            'uid' => $uid,
            'name' => $info['name']
        ];
    }
}
// 按姓名排序
usort($result, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
header('Content-Type: application/json');
echo json_encode($result);
?>