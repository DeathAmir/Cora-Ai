<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = null;
if ($id = user_id()) {
    $stmt = db()->prepare('SELECT id,name,email,avatar_seed,created_at FROM users WHERE id=? AND is_active=1 LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch() ?: null;
    if (!$user) unset($_SESSION['user_id']);
}
json_response([
    'authenticated' => $user !== null,
    'user' => $user,
    'csrf' => csrf_token(),
    'app' => ['name'=>$config['app']['name']],
    'ai' => ['provider'=>$config['ai']['provider'],'text_model'=>$config['ai']['text_model'],'image_model'=>$config['ai']['image_model']],
]);
