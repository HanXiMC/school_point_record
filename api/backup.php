<?php
require_once 'config.php';
ini_set('display_errors', 0);
error_reporting(0);

$action = $_GET['action'] ?? 'list';
if ($action === 'list') {
    $dirs = glob(BACKUP_PATH . '*', GLOB_ONLYDIR);
    $backups = [];
    foreach ($dirs as $dir) {
        $backups[] = [
            'name' => basename($dir),
            'time' => filemtime($dir)
        ];
    }
    usort($backups, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    header('Content-Type: application/json');
    echo json_encode($backups);
    exit;
}

if ($action === 'view') {
    $backupName = $_GET['backup'] ?? '';
    if (empty($backupName)) die('参数错误');
    $dir = BACKUP_PATH . $backupName;
    if (!is_dir($dir)) die('备份不存在');
    $files = glob($dir . '/*.json');
    $students = [];
    foreach ($files as $file) {
        if (basename($file) === 'summary.json') continue;
        $data = json_decode(file_get_contents($file), true);
        $students[] = [
            'name' => $data['name'],
            'score' => $data['score']
        ];
    }
    usort($students, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    header('Content-Type: application/json');
    echo json_encode($students);
    exit;
}
if ($action === 'get_backup_data') {
    $backupName = $_GET['backup'] ?? '';
    if (empty($backupName)) {
        echo json_encode(['success' => false, 'message' => '未指定快照名称']);
        exit;
    }
    $dir = BACKUP_PATH . $backupName;
    if (!is_dir($dir)) {
        echo json_encode(['success' => false, 'message' => '快照目录不存在']);
        exit;
    }
    $files = glob($dir . '/*.json');
    $students = [];
    foreach ($files as $file) {
        if (basename($file) === 'summary.json') continue;
        $data = json_decode(file_get_contents($file), true);
        $students[] = [
            'name'    => $data['name'],
            'score'   => $data['score'],
            'details' => $data['details'] ?? []
        ];
    }
    // 按姓名排序
    usort($students, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $students]);
    exit;
}

if ($action === 'download') {
    $backupName = $_GET['backup'] ?? '';
    $format = $_GET['format'] ?? 'zip';
    if (empty($backupName)) die('参数错误');
    $dir = BACKUP_PATH . $backupName;
    if (!is_dir($dir)) die('备份不存在');

    ob_clean();

    if ($format === 'zip') {
        $zipFile = sys_get_temp_dir() . '/' . $backupName . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $relativePath = $backupName . '/' . $file->getFilename();
                    $zip->addFile($file->getPathname(), $relativePath);
                }
            }
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $backupName . '.zip"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
            unlink($zipFile);
            exit;
        } else {
            die('无法创建zip');
        }
    } elseif ($format === 'csv') {
        $studentsData = [];
        $files = glob($dir . '/*.json');
        foreach ($files as $file) {
            if (basename($file) === 'summary.json') continue;
            $data = json_decode(file_get_contents($file), true);
            if (!empty($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $studentsData[] = [
                        '姓名' => $data['name'],
                        '时间' => $detail['time'],
                        '事由' => $detail['reason'],
                        '分数变化' => $detail['points'],
                        '操作人' => $detail['operator'],
                        '备注' => $detail['remark'] ?? ''
                    ];
                }
            }
        }
        // 按时间排序
        usort($studentsData, function($a, $b) {
            return strtotime($a['时间']) - strtotime($b['时间']);
        });

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $backupName . '_明细.xls"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        // 写入表头
        fputcsv($output, ['姓名', '时间', '事由', '分数变化', '操作人', '备注']);
        // 写入数据行
        foreach ($studentsData as $row) {
            // 为分数变化添加 +/- 符号
            $row['分数变化'] = ($row['分数变化'] >= 0 ? '+' : '') . $row['分数变化'];
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
?>