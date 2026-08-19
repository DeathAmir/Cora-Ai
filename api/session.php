<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = null;
$usage = ['chat'=>0,'image'=>0];
if ($id = user_id()) {
    $stmt = db()->prepare('SELECT id,name,email,avatar_seed,created_at FROM users WHERE id=? AND is_active=1 LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch() ?: null;
    if (!$user) unset($_SESSION['user_id']);
    else $usage = daily_usage((int)$user['id']);
}

json_response([
    'authenticated' => $user !== null,
    'user' => $user,
    'csrf' => csrf_token(),
    'app' => ['name'=>$config['app']['name']],
    'ai' => [
        'configured' => trim((string)$config['ai']['token']) !== '',
        'text_model' => $config['ai']['text_model'],
        'image_model' => $config['ai']['image_model'],
        'text_models' => $config['ai']['text_models'],
        'image_models' => $config['ai']['image_models'],
    ],
    'usage' => $usage,
    'limits' => ['chat'=>$config['app']['daily_chat_limit'],'image'=>$config['app']['daily_image_limit']],
]);
