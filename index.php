<?php 
define('BOT_TOKEN', '7522173790:');
// Получаем хуйню от Телеграма
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!isset($update['message'])) {
    exit;
}

$message = $update['message'];
$messageId = $message['message_id'];
$chatId = $message['chat']['id'];
$chatTitle = isset($message['chat']['title']) ? $message['chat']['title'] : 'Private/NoTitle';

// Собираем инфу о юзере, чтоб знать героев в лицо
$userId = $message['from']['id'];
$username = isset($message['from']['username']) ? '@'.$message['from']['username'] : 'NoUsername';
$firstName = isset($message['from']['first_name']) ? $message['from']['first_name'] : 'NoName';
$userInfo = "{$firstName} ({$username}, ID: {$userId})";

$text = isset($message['text']) ? $message['text'] : '';

// Если прислали медиа без текста (стикер, гифку, фото) - снесем и залогируем
if (empty($text)) {
    deleteMessage($chatId, $messageId);
    writeLog("DELETED (No Text/Media)", $userInfo, $chatId, $chatTitle, "[Медиа или пустой текст]");
    exit;
}

// Регулярка для номера
$phonePattern = '/(?:\+?[\d\s\-\(\)]{10,20})/';

$isPhoneValid = false;

if (preg_match($phonePattern, $text, $matches)) {
    $cleanPhone = preg_replace('/[^\d]/', '', $matches[0]);
    if (strlen($cleanPhone) >= 10) {
        $isPhoneValid = true;
    }
}

// Если номер есть — оставляем, если нет — удаляем нахуй
if ($isPhoneValid) {
    // Раскомментируй строчку ниже, если хочешь логировать И разрешенные сообщения тоже
    // writeLog("PASSED", $userInfo, $chatId, $chatTitle, $text);
} else {
    deleteMessage($chatId, $messageId);
    writeLog("DELETED", $userInfo, $chatId, $chatTitle, $text);
}

/**
 * Функция удаления сообщения
 */
function deleteMessage($chatId, $messageId) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage";
    $data = ['chat_id' => $chatId, 'message_id' => $messageId];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Функция логирования этой хуйни
 */
function writeLog($status, $user, $chatId, $chatTitle, $text) {
    $logFile = __DIR__ . '/bot_log.txt';
    $date = date('Y-m-d H:i:s');
    
    // Чистим текст от переносов строк, чтоб лог не расползался как сопли
    $singleLineText = str_replace(["\r", "\n"], ' ', $text);
    
    $logMessage = "[{$date}] [{$status}] | Chat: '{$chatTitle}' ({$chatId}) | User: {$user} | Text: \"{$singleLineText}\"\n";
    
    // Пишем в файл (FILE_APPEND значит добавлять в конец, а не перезаписывать)
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
?>
