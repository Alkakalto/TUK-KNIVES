<?php
require __DIR__.'/../app/bootstrap.php'; require_admin();
$filter=value($_GET,'publication',20,false); $args=[]; $where='deleted_at IS NULL';
if(in_array($filter,['draft','published','archived'],true)) { $where.=' AND publication=?'; $args[]=$filter; }
$page=max(1,(int)($_GET['page']??1)); $offset=($page-1)*30;
$rows=query('SELECT * FROM products WHERE '.$where.' ORDER BY updated_at DESC,id DESC LIMIT 31 OFFSET '.$offset,$args)->fetchAll();
$more=count($rows)>30; if($more) array_pop($rows);
$new=(int)one("SELECT COUNT(*) AS n FROM orders WHERE status='new'")['n'];
$mail=(int)one("SELECT COUNT(*) AS n FROM mail_outbox WHERE state<>'sent'")['n'];
page_start('Dina produkter'); ?>
<div class="actions"><a class="button" href="/admin/product.php">+ Lägg till produkt</a><a href="/admin/orders.php"><?=$new?> nya förfrågningar</a><a href="/catalog.php">Visa sortimentet</a></div>
<?php if($mail): ?><p class="notice"><a href="/admin/orders.php?mail=1"><?=$mail?> mejl väntar eller behöver kontrolleras.</a></p><?php endif ?>
<form method="get" class="filters"><?php select_field('publication','Visa produkter',[''=>'Alla','draft'=>'Utkast','published'=>'Publicerad','archived'=>'Arkiverad'],$filter); ?><button>Filtrera</button></form>
<div class="admin-list"><?php foreach($rows as $p): ?><article class="panel row"><div><p class="eyebrow"><?=e($p['number'])?></p><h2><a href="/admin/product.php?id=<?=$p['id']?>"><?=e($p['name'])?></a></h2><p><?=money($p['price_cents'])?></p></div><div><?=badge($p['publication'])?> <?=badge($p['availability'])?></div><a class="button secondary" href="/admin/product.php?id=<?=$p['id']?>">Redigera produkt</a></article><?php endforeach ?></div>
<?php if(!$rows): ?><div class="empty"><h2>Inga produkter här ännu</h2><p>Välj Lägg till produkt för att börja. Du kan spara som utkast och fortsätta senare.</p></div><?php endif ?>
<div class="actions"><?php if($page>1): ?><a href="?<?=e(http_build_query(['publication'=>$filter,'page'=>$page-1]))?>">Föregående</a><?php endif; if($more): ?><a href="?<?=e(http_build_query(['publication'=>$filter,'page'=>$page+1]))?>">Nästa</a><?php endif ?></div><?php page_end();
