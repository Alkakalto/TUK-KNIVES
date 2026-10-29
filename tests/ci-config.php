<?php
// Disposable CI database only. Never use this configuration for production.
return [
 'base_url'=>'http://127.0.0.1:8765', 'force_https'=>false,
 'app_key'=>hash('sha256','temporary-ci-test-key'),
 'db'=>['dsn'=>'mysql:host=127.0.0.1;port=3306;dbname=tuk_test;charset=utf8mb4','user'=>'root','password'=>getenv('TUK_TEST_DB_PASSWORD') ?: ''],
 'smtp'=>['host'=>'127.0.0.1','port'=>1,'encryption'=>'tls','username'=>'unused','password'=>'unused','from_email'=>'shop@example.test','from_name'=>'TUK test']
];
