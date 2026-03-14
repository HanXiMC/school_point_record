<?php
define('DATA_PATH', __DIR__ . '/../data/');
define('STUDENTS_PATH', DATA_PATH . 'students/');
define('BACKUP_PATH', DATA_PATH . 'backup/');
define('NAME_PATH', DATA_PATH . 'name/');
define('CONFIG_PATH', DATA_PATH . 'config/');

// 自动创建所需目录
foreach ([STUDENTS_PATH, BACKUP_PATH, NAME_PATH, CONFIG_PATH] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

function getAdminPassword() {
    $file = CONFIG_PATH . 'admin_pwd.txt';
    if (!file_exists($file)) {
        file_put_contents($file, '123456');
    }
    return trim(file_get_contents($file));
}

function setAdminPassword($newPwd) {
    file_put_contents(CONFIG_PATH . 'admin_pwd.txt', $newPwd);
}

function generateUID() {
    do {
        $uid = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    } while (file_exists(STUDENTS_PATH . $uid . '.json'));
    return $uid;
}

function getFirstLetter($name) {
    if (empty($name)) return '#';
    $firstChar = mb_substr($name, 0, 1, 'UTF-8');
    
    if (preg_match('/^[a-zA-Z]$/', $firstChar)) {
        return strtoupper($firstChar);
    }
    
    $gbk = @iconv('UTF-8', 'GBK//IGNORE', $firstChar);
    if (!$gbk || strlen($gbk) < 2) return '#';
    
    $asc = ord($gbk[0]) * 256 + ord($gbk[1]) - 65536;
    if ($asc >= -20319 && $asc <= -20284) return 'A';
    if ($asc >= -20283 && $asc <= -19776) return 'B';
    if ($asc >= -19775 && $asc <= -19219) return 'C';
    if ($asc >= -19218 && $asc <= -18711) return 'D';
    if ($asc >= -18710 && $asc <= -18527) return 'E';
    if ($asc >= -18526 && $asc <= -18240) return 'F';
    if ($asc >= -18239 && $asc <= -17923) return 'G';
    if ($asc >= -17922 && $asc <= -17418) return 'H';
    if ($asc >= -17417 && $asc <= -16475) return 'J';
    if ($asc >= -16474 && $asc <= -16213) return 'K';
    if ($asc >= -16212 && $asc <= -15641) return 'L';
    if ($asc >= -15640 && $asc <= -15166) return 'M';
    if ($asc >= -15165 && $asc <= -14923) return 'N';
    if ($asc >= -14922 && $asc <= -14915) return 'O';
    if ($asc >= -14914 && $asc <= -14631) return 'P';
    if ($asc >= -14630 && $asc <= -14150) return 'Q';
    if ($asc >= -14149 && $asc <= -14091) return 'R';
    if ($asc >= -14090 && $asc <= -13319) return 'S';
    if ($asc >= -13318 && $asc <= -12839) return 'T';
    if ($asc >= -12838 && $asc <= -12557) return 'W';
    if ($asc >= -12556 && $asc <= -11848) return 'X';
    if ($asc >= -11847 && $asc <= -11056) return 'Y';
    if ($asc >= -11055 && $asc <= -10247) return 'Z';
    
    return '#';
}

function getAllStudents() {
    $students = [];
    $files = glob(STUDENTS_PATH . '*.json');
    if (!$files) return $students;
    
    foreach ($files as $file) {
        $uid = basename($file, '.json');
        $content = @file_get_contents($file);
        if (!$content) continue;
        
        $data = json_decode($content, true);
        if ($data && isset($data['name'])) {
            $students[$uid] = [
                'name' => $data['name'],
                'initial' => $data['initial'] ?? '#',
                'score' => $data['score'] ?? 0
            ];
        }
    }
    return $students;
}
?>
