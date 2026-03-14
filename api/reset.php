<?php
require_once 'config.php';

$secret_token = 'mfuckyou'; 

if (!isset($_GET['token']) || $_GET['token'] !== $secret_token) {
    header('HTTP/1.1 403 Forbidden');
    exit('非法访问！');
}
$backupFolderName = date('Ymd_His'); 
$currentBackupPath = BACKUP_PATH . $backupFolderName;

if (!is_dir($currentBackupPath)) {
    mkdir($currentBackupPath, 0755, true);
}
$students = glob(STUDENTS_PATH . '*.json');
if ($students) {
    foreach ($students as $file) {
        copy($file, $currentBackupPath . '/' . basename($file));

        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            $data['score'] = 0;
            $data['details'] = [];
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

echo "重置成功，已备份原数据至快照：" . $backupFolderName;
?>