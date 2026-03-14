<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

if (ob_get_length()) ob_clean();

$action = $_GET['action'] ?? 'list';
$backupName = $_GET['backup'] ?? '';
$format = $_GET['format'] ?? '';

if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    $dirs = glob(BACKUP_PATH . '*', GLOB_ONLYDIR);
    $backups = [];
    foreach ($dirs as $dir) {
        $backups[] = [
            'name' => basename($dir),
            'time' => filemtime($dir)
        ];
    }
    usort($backups, function($a, $b) { return $b['time'] - $a['time']; });
    echo json_encode($backups);
    exit;
}

if ($action === 'get_backup_data') {
    header('Content-Type: application/json; charset=utf-8');
    $dir = BACKUP_PATH . $backupName;
    if (empty($backupName) || !is_dir($dir)) {
        echo json_encode([]);
        exit;
    }
    
    $files = glob($dir . '/*.json');
    $students = [];
    foreach ($files as $file) {
        if (basename($file) === 'summary.json') continue;
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (!$data) continue;
        
        $students[] = [
            'uid' => (string)basename($file, '.json'),
            'name' => $data['name'] ?? '未知',
            'score' => isset($data['score']) ? (int)$data['score'] : 0,
            'details' => isset($data['details']) ? $data['details'] : []
        ];
    }
    usort($students, function($a, $b) { return strcmp($a['name'], $b['name']); });
    echo json_encode($students);
    exit;
}

if ($action === 'download') {
    $dir = BACKUP_PATH . $backupName;
    if (!is_dir($dir)) die('备份文件夹不存在');

    if ($format === 'zip') {
        if (!class_exists('ZipArchive')) die('服务器未开启 ZipArchive 支持');
        
        $zipName = "Backup_" . $backupName . ".zip";
        $zipFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $jsonFiles = glob($dir . '/*.json');
            foreach ($jsonFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            if (ob_get_length()) ob_clean();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
            unlink($zipFile); 
            exit;
        } else {
            die("无法创建 ZIP 压缩包");
        }
    } 

    if ($format === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Score_Report_'.$backupName.'.csv"');
        echo "\xEF\xBB\xBF"; 
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['--- 总分统计 ---']);
        fputcsv($output, ['姓名', '当前总分']);
        
        $files = glob($dir . '/*.json');
        $allData = [];
        foreach ($files as $file) {
            if (basename($file) === 'summary.json') continue;
            $d = json_decode(file_get_contents($file), true);
            if ($d) {
                $allData[] = $d;
                fputcsv($output, [$d['name'], $d['score']]);
            }
        }
        
        fputcsv($output, []); 
        fputcsv($output, ['--- 加扣分明细 ---']);
        fputcsv($output, ['姓名', '时间', '事由', '分数', '操作人']);
        
        foreach ($allData as $s) {
            if (!empty($s['details'])) {
                foreach ($s['details'] as $dt) {
                    fputcsv($output, [
                        $s['name'],
                        $dt['time'] ?? '',
                        $dt['reason'] ?? '',
                        $dt['points'] ?? 0,
                        $dt['operator'] ?? ''
                    ]);
                }
            }
        }
        fclose($output);
        exit;
    }
}
?>