<?php
require_once 'config.php';

$students = getAllStudents();
$result = [];
foreach ($students as $uid => $info) {
    $result[] = [
        'uid' => $uid,
        'name' => $info['name'],
        'score' => $info['score']
    ];
}
// 按姓名排序
usort($result, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
header('Content-Type: application/json');
echo json_encode($result);
?>