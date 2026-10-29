<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/bootstrap.php';
$email=strtolower(email_value(['email'=>$argv[1]??''],'email'));
$password=rtrim(stream_get_contents(STDIN),"\r\n");
if(strlen($password)<16 || strlen($password)>72) throw new DomainException('Lösenordet måste vara 16–72 byte.');
$lock=one("SELECT GET_LOCK('tuk_create_first_owner',10) AS acquired");
if((int)$lock['acquired']!==1) throw new RuntimeException('Setup lock failed');
try {
    transaction(function() use($email,$password) {
        if((int)one('SELECT COUNT(*) AS n FROM users')['n']!==0) throw new DomainException('Ägarkontot finns redan. Ingen ny ägare skapades.');
        query('INSERT INTO users (email,password_hash) VALUES (?,?)',[$email,password_hash($password,PASSWORD_DEFAULT)]);
        query("UPDATE settings SET value=? WHERE name='owner_email'",[$email]);
    });
    echo "Ägarkontot skapat. Logga in på /admin.\n";
} finally { query("SELECT RELEASE_LOCK('tuk_create_first_owner')"); }
