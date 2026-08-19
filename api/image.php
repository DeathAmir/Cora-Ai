<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/AiClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Method not allowed'],405);
$userId = require_user();
$data = json_input();
require_csrf($data);
rate_limit('image', 10, 300);

$prompt = clean_text((string)($data['prompt'] ?? ''), 6000);
$size = (string)($data['size'] ?? '1024x1024');
if ($prompt === '') json_response(['error'=>'توضیح تصویر خالی است.'],422);
$pdo = db();
$conversationId = isset($data['conversation_id']) ? (int)$data['conversation_id'] : 0;
if ($conversationId > 0) {
    conversation_owner($conversationId,$userId);
} else {
    $title = clean_text('تصویر: '.preg_replace('/\s+/u',' ',$prompt),70);
    $pdo->prepare('INSERT INTO conversations(user_id,title,mode,model) VALUES(?,?,\'image\',?)')->execute([$userId,$title,$config['ai']['image_model']]);
    $conversationId = (int)$pdo->lastInsertId();
}
$pdo->prepare('INSERT INTO messages(conversation_id,role,kind,content) VALUES(?,\'user\',\'text\',?)')->execute([$conversationId,$prompt]);

try {
    $client = new AiClient($config['ai']);
    $result = $client->image($prompt,$size);
    $meta = json_encode(['model'=>$config['ai']['image_model'],'prompt'=>$prompt,'size'=>$size],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $pdo->prepare('INSERT INTO messages(conversation_id,role,kind,content,meta_json) VALUES(?,\'assistant\',\'image\',?,?)')->execute([$conversationId,$result['url'],$meta]);
    $pdo->prepare('UPDATE conversations SET model=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$config['ai']['image_model'],$conversationId,$userId]);
    json_response(['ok'=>true,'conversation_id'=>$conversationId,'image'=>$result['url'],'model'=>$config['ai']['image_model']]);
} catch (Throwable $e) {
    error_log('Cora AI image: '.$e->getMessage());
    $safe = $config['app']['debug'] ? $e->getMessage() : 'سرویس ساخت تصویر در دسترس نیست یا تنظیم نشده است.';
    json_response(['error'=>$safe,'conversation_id'=>$conversationId],502);
}
