<?php
require __DIR__.'/../app/bootstrap.php'; require_admin(); check_csrf();
$_SESSION=[]; session_destroy();
setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
redirect('/admin/login.php');
