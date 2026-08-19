<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/AiClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Method not allowed'],405);
$userId=require_user();$data=json_input();require_csrf($data);rate_limit('image',6,300);enforce_daily_limit($userId,'image');
$prompt=clean_text((string)($data['prompt']??''),6000);$size=(string)($data['size']??'1024x1024');$model=clean_text((string)($data['model']??$config['ai']['image_model']),160);
if($prompt==='') json_response(['error'=>'توضیح تصویر خالی است.'],422);if(!model_allowed('image',$model)) json_response(['error'=>'مدل تصویر انتخاب‌شده مجاز نیست.'],422);if(!in_array($size,['1024x1024','768x1024','1024x768'],true))$size='1024x1024';
$pdo=db();$conversationId=isset($data['conversation_id'])?(int)$data['conversation_id']:0;
if($conversationId>0){conversation_owner($conversationId,$userId);}else{$title=clean_text('تصویر: '.(string)preg_replace('/\s+/u',' ',$prompt),70);$pdo->prepare("INSERT INTO conversations(user_id,title,mode,model) VALUES(?,?,'image',?)")->execute([$userId,$title?:'New Image',$model]);$conversationId=(int)$pdo->lastInsertId();}
$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content) VALUES(?,'user','text',?)")->execute([$conversationId,$prompt]);
try{$client=new AiClient($config['ai']);$result=$client->image($prompt,$size,$model);$meta=json_encode(['model'=>$result['model'],'prompt'=>$prompt,'size'=>$size],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$pdo->prepare("INSERT INTO messages(conversation_id,role,kind,content,meta_json) VALUES(?,'assistant','image',?,?)")->execute([$conversationId,$result['url'],$meta]);$pdo->prepare('UPDATE conversations SET model=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$result['model'],$conversationId,$userId]);json_response(['ok'=>true,'conversation_id'=>$conversationId,'image'=>$result['url'],'model'=>$result['model'],'usage'=>daily_usage($userId)]);}catch(Throwable $e){error_log('Cora AI image: '.$e->getMessage());$safe=$config['app']['debug']?$e->getMessage():'ساخت تصویر انجام نشد. توکن/اعتبار Hugging Face یا وضعیت FLUX را بررسی کن.';json_response(['error'=>$safe,'conversation_id'=>$conversationId],502);}
