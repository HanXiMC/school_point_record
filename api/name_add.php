<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = '仅支持POST请求';
    echo json_encode($response);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['names'])) {
        throw new Exception('无效的请求数据');
    }

    $namesStr = $input['names'];
    
    $namesStr = str_replace(['，', '、', ' ', "\r", "\n"], ',', $namesStr);
    $names = array_map('trim', explode(',', $namesStr));
    $names = array_filter($names);

    if (empty($names)) {
        throw new Exception('没有输入任何姓名');
    }

    $existing = getAllStudents();
    $existingNames = array_column($existing, 'name');

    $newCount = 0;
    foreach ($names as $name) {
        if (empty($name)) continue;
        if (in_array($name, $existingNames)) continue;
        
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
    $response['message'] = "成功导入 $newCount 名学生。";
} catch (Exception $e) {
    $response['message'] = '错误：' . $e->getMessage();
}

echo json_encode($response);
?>
