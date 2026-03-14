<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$uid = $input['uid'] ?? '';
$log_id = $input['log_id'] ?? '';
$time = $input['time'] ?? '';
$reason = $input['reason'] ?? '';
$points = $input['points'] ?? 0;

if (empty($uid)) {
    echo json_encode(['success' => false, 'message' => '参数不完整']);
    exit;
}

$file = STUDENTS_PATH . $uid . '.json';
if (!file_exists($file)) {
    echo json_encode(['success' => false, 'message' => '学生不存在']);
    exit;
}

$fp = fopen($file, 'c+');
if (flock($fp, LOCK_EX)) {
    $data = json_decode(file_get_contents($file), true);
    $details = $data['details'] ?? [];
    
    $foundIndex = -1;
    foreach ($details as $index => $detail) {
        if (!empty($log_id) && isset($detail['log_id']) && $detail['log_id'] === $log_id) {
            $foundIndex = $index;
            break;
        } 
        elseif (empty($log_id) || !isset($detail['log_id'])) {
            if ($detail['time'] === $time && $detail['reason'] === $reason && (int)$detail['points'] === (int)$points) {
                $foundIndex = $index;
                break;
            }
        }
    }

    if ($foundIndex !== -1) {
        $undoPoints = (int)$details[$foundIndex]['points'];
        $data['score'] -= $undoPoints; 
        array_splice($data['details'], $foundIndex, 1); 
        
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        echo json_encode(['success' => true, 'message' => '撤销成功，分数已回滚']);
    } else {
        flock($fp, LOCK_UN);
        fclose($fp);
        echo json_encode(['success' => false, 'message' => '未找到对应的操作记录，可能已被删除']);
    }
} else {
    fclose($fp);
    echo json_encode(['success' => false, 'message' => '文件锁定失败，请重试']);
}
?>