<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/AiClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Method not allowed'],405);
$userId=require_user(); $data=json_input(); require_csrf($data); rate_limit('chat',30,300); enforce_daily_limit($userId,'chat');
$prompt=clean_text((string)($data['message']??''),$config['app']['max_prompt_chars']);
$model=clean_text((string)($data['model']??$config['ai']['text_model']),160);
if($prompt==='') json_response(['error'=>'پیام خالی است.'],422);
if(!model_allowed('chat',$model)) json_response(['error'=>'مدل انتخاب‌شده مجاز نیست.'],422);
$conversationId=isset($data['conversation_id'])?(int)$data['conversation_id']:0; $pdo=db();
if($conversationId>0){conversation_owner($conversationId,$userId);}else{$title=clean_text((string)preg_replace('/\s+/u',' ',$prompt),70);$pdo->prepare("INSERT INTO conversations(user_id,title,mode,model) VALUES(?,?,'chat',?)")->execute([$userId,$title?:'New Chat',$model]);$conversationId=(int)$pdo->lastInsertId();}
$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content) VALUES(?,'user','text',?)")->execute([$conversationId,$prompt]);
$stmt=$pdo->prepare("SELECT role,content FROM messages WHERE conversation_id=? AND kind='text' AND role IN ('user','assistant') ORDER BY id DESC LIMIT 24");$stmt->execute([$conversationId]);$history=array_reverse($stmt->fetchAll());$messages=array_map(static fn($m)=>['role'=>$m['role'],'content'=>$m['content']],$history);
try{$client=new AiClient($config['ai']);$result=$client->chat($messages,$model);$answer=clean_text($result['text'],120000);$meta=json_encode(['model'=>$result['model']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content,meta_json) VALUES(?,'assistant','text',?,?)")->execute([$conversationId,$answer,$meta]);$pdo->prepare('UPDATE conversations SET model=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$result['model'],$conversationId,$userId]);json_response(['ok'=>true,'conversation_id'=>$conversationId,'message'=>$answer,'model'=>$result['model'],'usage'=>daily_usage($userId)]);}catch(Throwable $e){error_log('Cora AI chat: '.$e->getMessage());$safe=$config['app']['debug']?$e->getMessage():'اتصال به Hugging Face برقرار نشد. توکن، اعتبار و مدل را بررسی کن.';$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content) VALUES(?,'assistant','error',?)")->execute([$conversationId,$safe]);json_response(['error'=>$safe,'conversation_id'=>$conversationId],502);}
