<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $txtFiles = glob(NAME_PATH . '*.txt');
    if (empty($txtFiles)) {
        throw new Exception('/data/name/ 目录下没有找到任何 .txt 文件');
    }

    $names = [];
    foreach ($txtFiles as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("无法读取文件：$file");
        }
        

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $lines = array_filter(array_map('trim', $lines));
        
        foreach ($lines as $line) {
            if (!empty($line)) $names[] = $line;
        }
    }
    $names = array_unique($names);

    $existing = getAllStudents();
    $existingNames = array_column($existing, 'name');

    $newCount = 0;
    foreach ($names as $name) {
        if (in_array($name, $existingNames)) {
            continue; 
        }
        $uid = generateUID();
        $initial = getFirstLetter($name);
        $studentData = [
            'name' => $name,
            'initial' => $initial,
            'score' => 0,
            'details' => []
        ];
        if (file_put_contents(STUDENTS_PATH . $uid . '.json', json_encode($studentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new Exception("无法写入学生文件：$uid.json");
        }
        $newCount++;
    }

    $response['success'] = true;
    $response['message'] = "导入完成，新增 $newCount 名学生。";
} catch (Exception $e) {
    $response['message'] = '错误：' . $e->getMessage();
}

echo json_encode($response);
?>
