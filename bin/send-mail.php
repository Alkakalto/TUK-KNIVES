<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/bootstrap.php';
// Only pending messages. Failed/uncertain deliveries require explicit owner review.
foreach(query("SELECT id FROM mail_outbox WHERE state='pending' ORDER BY id LIMIT 20")->fetchAll() as $m) dispatch_mail((int)$m['id']);
echo "Mejlkön kontrollerad.\n";
