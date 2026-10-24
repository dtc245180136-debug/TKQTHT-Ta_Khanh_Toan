<?php
declare(strict_types=1);
ini_set('display_errors', '0');
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Strict','use_strict_mode'=>true]);
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function secret(string $name): string { return trim(file_get_contents('/run/secrets/'.$name)); }
function db(): PDO { static $pdo; return $pdo ??= new PDO('mysql:host=mysql;dbname=portfolio;charset=utf8mb4','portfolio',secret('db_password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]); }
function audit(string $event): void { file_put_contents('/logs/app/events.log',json_encode(['time'=>gmdate('c'),'event'=>$event],JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND|LOCK_EX); }
function go(string $path): never { header('Location: '.$path); exit; }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if ($path==='/style.css') { header('Content-Type: text/css'); readfile(__DIR__.'/style.css'); exit; }
try {
 if ($path==='/health') { db()->query('SELECT 1'); header('Content-Type: application/json'); echo '{"status":"ok"}'; exit; }
 $error='';
 if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf'],$_POST['csrf']??'')) { http_response_code(403); exit('CSRF không hợp lệ'); }
  if ($path==='/login') {
   $q=db()->prepare('SELECT * FROM admins WHERE username=?'); $q->execute([substr($_POST['username']??'',0,80)]); $user=$q->fetch(PDO::FETCH_ASSOC);
   if ($user && password_verify($_POST['password']??'',$user['password_hash'])) { session_regenerate_id(true); $_SESSION['admin']=$user['id']; audit('login_success'); go('/admin'); }
   audit('login_failed'); http_response_code(401); $error='Tài khoản hoặc mật khẩu không đúng.';
  } else {
   if (empty($_SESSION['admin'])) { http_response_code(401); exit('Cần đăng nhập'); }
   if ($path==='/logout') { session_destroy(); go('/'); }
   if ($path==='/admin/profile') {
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
    if (!$name || mb_strlen_safe($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Tên hoặc email không hợp lệ.');
    $q=db()->prepare('UPDATE profile SET name=?,role=?,bio=?,email=?,skills=? WHERE id=1');
    $q->execute([$name,substr($_POST['role']??'',0,150),substr($_POST['bio']??'',0,4000),$email,substr($_POST['skills']??'',0,1000)]); audit('profile_updated'); go('/admin');
   }
   if ($path==='/admin/project') {
    $id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT); $title=trim($_POST['title']??''); $url=trim($_POST['url']??'');
    if (!$title || strlen($title)>180 || ($url && (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(parse_url($url,PHP_URL_SCHEME),['http','https'],true)))) throw new InvalidArgumentException('Tiêu đề hoặc URL không hợp lệ.');
    $values=[$title,substr($_POST['description']??'',0,3000),substr($_POST['tech']??'',0,250),$url,isset($_POST['published'])?1:0];
    if ($id) { $q=db()->prepare('UPDATE projects SET title=?,description=?,tech=?,url=?,published=? WHERE id=?'); $values[]=$id; } else $q=db()->prepare('INSERT INTO projects(title,description,tech,url,published) VALUES(?,?,?,?,?)');
    $q->execute($values); audit('project_saved'); go('/admin');
   }
   if ($path==='/admin/delete') { $q=db()->prepare('DELETE FROM projects WHERE id=?'); $q->execute([(int)($_POST['id']??0)]); audit('project_deleted'); go('/admin'); }
  }
 }
 if (!in_array($path,['/','/login','/admin'],true)) { http_response_code(404); exit('Không tìm thấy trang'); }
 if ($path==='/admin' && empty($_SESSION['admin'])) go('/login');
 $profile=db()->query('SELECT * FROM profile WHERE id=1')->fetch(PDO::FETCH_ASSOC);
 $projects=db()->query('SELECT * FROM projects'.($path==='/admin'?'':' WHERE published=1').' ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (InvalidArgumentException $e) { http_response_code(422); exit(esc($e->getMessage()).' <a href="/admin">Quay lại</a>'); }
catch (Throwable $e) { error_log('portfolio request failed'); http_response_code(503); exit('Dịch vụ tạm thời chưa sẵn sàng.'); }
function mb_strlen_safe(string $s): int { return strlen($s); }
function csrf(): void { echo '<input type="hidden" name="csrf" value="'.esc($_SESSION['csrf']).'">'; }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=esc($profile['name'])?> · Portfolio</title><link rel="stylesheet" href="/style.css"></head><body>
<header><a class="brand" href="/">FOLIO<span> / 13</span></a><nav><a href="/#work">Dự án</a><a href="/#about">Giới thiệu</a><a href="mailto:<?=esc($profile['email'])?>">Liên hệ ↗</a><a href="/admin">Quản trị</a></nav></header>
<main>
<?php if ($path==='/login'): ?>
<section class="login"><p class="eyebrow">KHÔNG GIAN QUẢN TRỊ</p><h1>Chào trở lại.</h1><p role="alert"><?=esc($error)?></p><form method="post" action="/login"><?php csrf(); ?><label>Tài khoản<input name="username" autocomplete="username" required maxlength="80"></label><label>Mật khẩu<input name="password" type="password" autocomplete="current-password" required></label><button>Đăng nhập →</button></form></section>
<?php elseif ($path==='/admin'): ?>
<section><p class="eyebrow">CONTENT STUDIO</p><h1>Quản lý portfolio.</h1><form method="post" action="/logout"><?php csrf(); ?><button class="secondary">Đăng xuất</button></form></section>
<div class="admin-grid"><section class="panel"><h2>Hồ sơ cá nhân</h2><form method="post" action="/admin/profile"><?php csrf(); ?><?php foreach (['name'=>'Họ tên','role'=>'Chức danh','email'=>'Email','skills'=>'Kỹ năng (phân cách bằng dấu phẩy)'] as $key=>$label): ?><label><?=esc($label)?><input name="<?=esc($key)?>" value="<?=esc($profile[$key])?>" required></label><?php endforeach; ?><label>Giới thiệu<textarea name="bio" required><?=esc($profile['bio'])?></textarea></label><button>Lưu hồ sơ</button></form></section>
<section class="panel"><h2>Thêm dự án</h2><?php $item=['id'=>0,'title'=>'','description'=>'','tech'=>'','url'=>'','published'=>1]; require __DIR__.'/project-form.php'; ?></section></div>
<section><h2>Các dự án hiện có</h2><div class="grid"><?php foreach ($projects as $item): ?><article class="panel"><h3><?=esc($item['title'])?></h3><?php require __DIR__.'/project-form.php'; ?><form method="post" action="/admin/delete"><?php csrf(); ?><input type="hidden" name="id" value="<?=(int)$item['id']?>"><button class="danger">Xóa dự án</button></form></article><?php endforeach; ?></div></section>
<?php else: ?>
<section class="hero"><div><p class="eyebrow"><span class="dot"></span> SẴN SÀNG CHO NHỮNG Ý TƯỞNG MỚI</p><h1>Xin chào,<br>mình là <em><?=esc($profile['name'])?>.</em></h1><p class="intro"><?=esc($profile['role'])?></p><a class="button" href="#work">Khám phá dự án ↓</a></div><div class="art" aria-hidden="true"><div class="orb"></div><span>DESIGN · BUILD · DEPLOY</span><strong>Ý tưởng tốt.<br>Triển khai thật.</strong><small>PERSONAL PORTFOLIO / 2026</small></div></section>
<section id="work"><div class="section-head"><div><p class="eyebrow">SELECTED WORK</p><h2>Dự án nổi bật<span>.</span></h2></div><p><?=count($projects)?> dự án / Không ngừng học hỏi</p></div><div class="grid"><?php foreach ($projects as $i=>$item): ?><article class="project"><div class="project-art color-<?=$i%3?>"><span><?=sprintf('%02d',$i+1)?></span><strong><?=esc($item['tech'])?></strong></div><div class="project-body"><p class="eyebrow"><?=esc($item['tech'])?></p><h3><?=esc($item['title'])?></h3><p><?=esc($item['description'])?></p><?php if ($item['url']): ?><a href="<?=esc($item['url'])?>" target="_blank" rel="noopener noreferrer">Xem dự án ↗</a><?php endif; ?></div></article><?php endforeach; ?><?php if (!$projects): ?><p>Dự án mới sẽ sớm được cập nhật.</p><?php endif; ?></div></section>
<section id="about" class="about"><div><p class="eyebrow">MỘT CHÚT VỀ MÌNH</p><h2>Tò mò học hỏi.<br>Tỉ mỉ xây dựng.</h2></div><div><p class="intro"><?=nl2br(esc($profile['bio']))?></p><div class="tags"><?php foreach (explode(',',$profile['skills']) as $skill): ?><span><?=esc(trim($skill))?></span><?php endforeach; ?></div></div></section>
<section class="contact"><p class="eyebrow">LET’S MAKE SOMETHING GREAT</p><h2>Cùng tạo nên<br>điều tiếp theo.</h2><a class="button" href="mailto:<?=esc($profile['email'])?>"><?=esc($profile['email'])?> ↗</a></section>
<?php endif; ?></main><footer>© <?=date('Y')?> <?=esc($profile['name'])?> <span>Được xây dựng bằng sự tò mò & cà phê.</span></footer></body></html>
