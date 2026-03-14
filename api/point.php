<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$time = $input['time'] ?? date('Y-m-d H:i:s');
$uids = $input['uids'] ?? [];
$reason = $input['reason'] ?? '';
$customReason = $input['customReason'] ?? '';
$remark = $input['remark'] ?? '';
$points = intval($input['points'] ?? 0);
$operator = $input['operator'] ?? '';
$customOperator = $input['customOperator'] ?? '';
$subject = $input['subject'] ?? '';
$type = $input['type'] ?? 'subtract';

if (empty($uids)) {
    echo json_encode(['success' => false, 'message' => '请选择学生']);
    exit;
}

if ($reason === '自定义' && !empty($customReason)) {
    $reason = $customReason;
}
if ($operator === '自定义' && !empty($customOperator)) {
    $operator = $customOperator;
}
if ($operator === '课代表' && !empty($subject)) {
    $operator .= "（$subject）";
}

$detail = [
    'log_id' => uniqid('log_'),
    'time' => $time,
    'reason' => $reason,
    'points' => $type === 'add' ? +$points : -$points,
    'operator' => $operator,
    'remark' => $remark
];

$locks = [];
$success = true;
$failedUids = [];

foreach ($uids as $uid) {
    $file = STUDENTS_PATH . $uid . '.json';
    if (!file_exists($file)) {
        $failedUids[] = $uid;
        $success = false;
        break;
    }
    $fp = fopen($file, 'c+');
    if (flock($fp, LOCK_EX | LOCK_NB)) {
        $locks[] = ['fp' => $fp, 'file' => $file, 'uid' => $uid];
    } else {
        $failedUids[] = $uid;
        $success = false;
        break;
    }
}

if ($success && empty($failedUids)) {
    foreach ($locks as $lock) {
        $fp = $lock['fp'];
        $data = json_decode(file_get_contents($lock['file']), true);
        $data['details'][] = $detail;
        $data['score'] += $detail['points'];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    echo json_encode(['success' => true, 'message' => '操作成功']);
} else {
    foreach ($locks as $lock) {
        flock($lock['fp'], LOCK_UN);
        fclose($lock['fp']);
    }
    $msg = empty($failedUids) ? '操作失败，请重试' : '以下学生文件被占用，请稍后重试：' . implode('，', $failedUids);
    echo json_encode(['success' => false, 'message' => $msg]);
}
?>