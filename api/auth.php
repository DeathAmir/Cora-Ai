<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
$data = json_input();
require_csrf($data);
$action = (string)($data['action'] ?? '');
rate_limit('auth_' . $action, 12, 600);

if ($action === 'register') {
    $name = clean_text((string)($data['name'] ?? ''), 80);
    $email = mb_strtolower(clean_text((string)($data['email'] ?? ''), 190));
    $password = (string)($data['password'] ?? '');
    if (mb_strlen($name) < 2) json_response(['error' => 'نام باید حداقل ۲ کاراکتر باشد.'], 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'ایمیل معتبر نیست.'], 422);
    if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        json_response(['error' => 'رمز عبور حداقل ۱۰ کاراکتر و شامل حرف و عدد باشد.'], 422);
    }
    $stmt = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) json_response(['error' => 'این ایمیل قبلاً ثبت شده است.'], 409);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $seed = bin2hex(random_bytes(16));
    db()->prepare('INSERT INTO users(name,email,password_hash,avatar_seed) VALUES(?,?,?,?)')->execute([$name,$email,$hash,$seed]);
    $id = (int)db()->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    json_response(['ok' => true, 'user' => ['id'=>$id,'name'=>$name,'email'=>$email], 'csrf'=>$_SESSION['csrf']]);
}

if ($action === 'login') {
    $email = mb_strtolower(clean_text((string)($data['email'] ?? ''), 190));
    $password = (string)($data['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,password_hash,is_active FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) json_response(['error' => 'ایمیل یا رمز عبور اشتباه است.'], 401);
    if (!(int)$user['is_active']) json_response(['error' => 'این حساب غیرفعال است.'], 403);
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]);
    json_response(['ok'=>true,'user'=>['id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['email']], 'csrf'=>$_SESSION['csrf']]);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
    json_response(['ok'=>true]);
}

json_response(['error' => 'عملیات نامعتبر است.'], 400);
