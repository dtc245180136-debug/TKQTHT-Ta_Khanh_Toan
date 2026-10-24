<?php
require_once __DIR__.'/schema.php';
$pdo=new PDO('mysql:host=mysql;dbname=portfolio;charset=utf8mb4','portfolio',trim(file_get_contents('/run/secrets/db_password')),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach ($schema as $sql) $pdo->exec($sql);
$fresh=(int)$pdo->query('SELECT COUNT(*) FROM profile')->fetchColumn()===0;
$q=$pdo->prepare('INSERT IGNORE INTO admins(id,username,password_hash) VALUES(1,?,?)');
$q->execute(['admin',password_hash(trim(file_get_contents('/run/secrets/admin_password')),PASSWORD_DEFAULT)]);
$pdo->exec("INSERT IGNORE INTO profile VALUES(1,'Minh Anh','Lập trình viên web & người yêu công nghệ','Mình xây dựng những trải nghiệm web đơn giản, hữu ích và dễ sử dụng. Mình quan tâm đến cả chất lượng sản phẩm lẫn cách hệ thống vận hành ổn định.','hello@example.com','PHP, MySQL, Docker, Nginx, Linux')");
if (!$fresh) { echo "Already initialized\n"; exit; }
$q=$pdo->prepare('INSERT IGNORE INTO projects(id,title,description,tech,url,published) VALUES(?,?,?,?,?,1)');
$q->execute([1,'Hệ thống đặt phòng khách sạn','Quản lý phòng, đặt chỗ và vận hành lưu trú trên một hệ thống thống nhất.','Node.js · PostgreSQL · Docker','']);
$q->execute([2,'Portfolio & Content Studio','Website cá nhân với quản trị nội dung, triển khai bằng Docker và giám sát tập trung.','PHP · MySQL · Observability','']);
echo "Bootstrap complete\n";
