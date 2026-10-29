<?php
require __DIR__.'/app/bootstrap.php';
$ref=value($_GET,'ref',40);
if(empty($_SESSION['receipts'][$ref])) { http_response_code(404); exit('Bekräftelsen finns inte i denna session.'); }
$o=one('SELECT id,reference FROM orders WHERE reference=?',[$ref]);
if(!$o) throw new DomainException('Förfrågan kunde inte hittas.');
// A failure to deliver mail never affects the saved receipt or asks the customer to resubmit.
dispatch_order_mail((int)$o['id']);
page_start('Din förfrågan är sparad'); ?>
<div class="panel"><p class="eyebrow">Referensnummer</p><h2><?=e($ref)?></h2><p><?=e(REQUEST_NOTICE)?></p><p>Du behöver inte skicka förfrågan igen, även om mottagningsmejlet dröjer. Spara referensnumret så kan vi hjälpa dig vid frågor.</p><a class="button" href="/catalog.php">Tillbaka till sortimentet</a></div><?php page_end();
