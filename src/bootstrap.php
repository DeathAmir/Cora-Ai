<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';

ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name('cora_session');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Strict']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
header('Cross-Origin-Opener-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
if ($isHttps) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

function db(): PDO {
    static $pdo = null; global $config;
    if ($pdo instanceof PDO) return $pdo;
    $d=$config['db'];
    $pdo=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset={$d['charset']}",$d['user'],$d['pass'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS=>false,
    ]);
    return $pdo;
}
function json_input(): array { $raw=file_get_contents('php://input'); if($raw===false||strlen($raw)>120000) json_response(['error'=>'درخواست بیش از حد بزرگ است.'],413); $data=json_decode($raw?:'{}',true); return is_array($data)?$data:[]; }
function json_response(array $data,int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function require_csrf(array $data=[]): void { $token=$data['csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??''); if(!is_string($token)||!hash_equals($_SESSION['csrf']??'',$token)) json_response(['error'=>'نشست معتبر نیست. صفحه را تازه‌سازی کنید.'],419); }
function user_id(): ?int { return isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:null; }
function require_user(): int { $id=user_id(); if(!$id) json_response(['error'=>'ابتدا وارد حساب شوید.'],401); return $id; }
function clean_text(string $value,int $max): string { $value=trim(str_replace("\0",'',$value)); return mb_substr($value,0,$max); }
function client_key(): string { $ip=$_SERVER['REMOTE_ADDR']??'0.0.0.0'; return hash('sha256',$ip.'|'.($_SERVER['HTTP_USER_AGENT']??'')); }
function rate_limit(string $action,int $limit,int $windowSeconds): void {
    $key=client_key(); $pdo=db(); $pdo->beginTransaction();
    try {
        $stmt=$pdo->prepare('SELECT id,hits,window_start FROM rate_limits WHERE bucket_key=? AND action_name=? FOR UPDATE'); $stmt->execute([$key,$action]); $row=$stmt->fetch(); $now=time();
        if(!$row){$pdo->prepare('INSERT INTO rate_limits(bucket_key,action_name,hits,window_start) VALUES(?,?,1,NOW())')->execute([$key,$action]);}
        else { $start=strtotime((string)$row['window_start']); if($now-$start>=$windowSeconds){$pdo->prepare('UPDATE rate_limits SET hits=1,window_start=NOW() WHERE id=?')->execute([$row['id']]);} elseif((int)$row['hits']>=$limit){$pdo->rollBack(); json_response(['error'=>'درخواست‌های زیادی ارسال شده. کمی بعد دوباره تلاش کنید.'],429);} else {$pdo->prepare('UPDATE rate_limits SET hits=hits+1 WHERE id=?')->execute([$row['id']]);} }
        $pdo->commit();
    } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); error_log($e->getMessage()); }
}
function model_allowed(string $kind,string $model): bool { global $config; $key=$kind==='image'?'image_models':'text_models'; foreach($config['ai'][$key] as $item){if(hash_equals((string)$item['id'],$model))return true;} return false; }
function daily_usage(int $userId): array {
    $stmt=db()->prepare("SELECT m.kind,COUNT(*) total FROM messages m INNER JOIN conversations c ON c.id=m.conversation_id WHERE c.user_id=? AND m.role='assistant' AND m.created_at>=CURDATE() AND m.kind IN ('text','image') GROUP BY m.kind");
    $stmt->execute([$userId]); $usage=['chat'=>0,'image'=>0];
    foreach($stmt->fetchAll() as $row){if($row['kind']==='image')$usage['image']=(int)$row['total']; if($row['kind']==='text')$usage['chat']=(int)$row['total'];}
    return $usage;
}
function enforce_daily_limit(int $userId,string $kind): void { global $config; $usage=daily_usage($userId); $limit=$kind==='image'?$config['app']['daily_image_limit']:$config['app']['daily_chat_limit']; if(($usage[$kind]??0)>=$limit) json_response(['error'=>'سهمیه روزانه این بخش تمام شده است. فردا دوباره قابل استفاده است.','usage'=>$usage],429); }
function conversation_owner(int $conversationId,int $userId): array { $stmt=db()->prepare('SELECT * FROM conversations WHERE id=? AND user_id=? AND deleted_at IS NULL'); $stmt->execute([$conversationId,$userId]); $row=$stmt->fetch(); if(!$row) json_response(['error'=>'گفتگو پیدا نشد.'],404); return $row; }
