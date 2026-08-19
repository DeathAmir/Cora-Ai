<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
$userId = require_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $conversation = conversation_owner($id, $userId);
        $stmt = $pdo->prepare('SELECT id,role,kind,content,meta_json,created_at FROM messages WHERE conversation_id=? ORDER BY id ASC LIMIT 500');
        $stmt->execute([$id]);
        $messages = $stmt->fetchAll();
        foreach ($messages as &$m) $m['meta'] = $m['meta_json'] ? json_decode($m['meta_json'], true) : null;
        json_response(['conversation'=>$conversation,'messages'=>$messages]);
    }
    $q = clean_text((string)($_GET['q'] ?? ''), 80);
    if ($q !== '') {
        $stmt = $pdo->prepare('SELECT id,title,mode,model,pinned,updated_at FROM conversations WHERE user_id=? AND deleted_at IS NULL AND title LIKE ? ORDER BY pinned DESC,updated_at DESC LIMIT 80');
        $stmt->execute([$userId, '%'.$q.'%']);
    } else {
        $stmt = $pdo->prepare('SELECT id,title,mode,model,pinned,updated_at FROM conversations WHERE user_id=? AND deleted_at IS NULL ORDER BY pinned DESC,updated_at DESC LIMIT 80');
        $stmt->execute([$userId]);
    }
    json_response(['conversations'=>$stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_input();
    require_csrf($data);
    $action = (string)($data['action'] ?? '');
    $id = (int)($data['id'] ?? 0);
    conversation_owner($id, $userId);
    if ($action === 'delete') {
        $pdo->prepare('UPDATE conversations SET deleted_at=NOW() WHERE id=? AND user_id=?')->execute([$id,$userId]);
        json_response(['ok'=>true]);
    }
    if ($action === 'rename') {
        $title = clean_text((string)($data['title'] ?? ''), 120);
        if ($title === '') json_response(['error'=>'عنوان خالی است.'],422);
        $pdo->prepare('UPDATE conversations SET title=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$title,$id,$userId]);
        json_response(['ok'=>true,'title'=>$title]);
    }
    if ($action === 'pin') {
        $pinned = !empty($data['pinned']) ? 1 : 0;
        $pdo->prepare('UPDATE conversations SET pinned=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$pinned,$id,$userId]);
        json_response(['ok'=>true,'pinned'=>$pinned]);
    }
    json_response(['error'=>'عملیات نامعتبر است.'],400);
}

json_response(['error'=>'Method not allowed'],405);
