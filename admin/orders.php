<?php
require __DIR__.'/../app/bootstrap.php'; require_admin();
$status=value($_GET,'status',30,false); $args=[]; $where='1=1';
if(array_key_exists($status,order_statuses())) { $where.=' AND o.status=?'; $args[]=$status; }
if(isset($_GET['mail'])) $where.=" AND EXISTS (SELECT 1 FROM mail_outbox m WHERE m.order_id=o.id AND m.state<>'sent')";
$page=max(1,(int)($_GET['page']??1)); $offset=($page-1)*30;
$rows=query('SELECT o.* FROM orders o WHERE '.$where.' ORDER BY o.created_at DESC,o.id DESC LIMIT 31 OFFSET '.$offset,$args)->fetchAll(); $more=count($rows)>30; if($more) array_pop($rows);
page_start('Beställningsförfrågningar'); ?>
<form method="get" class="filters"><?php select_field('status','Status',[''=>'Alla']+order_statuses(),$status); ?><label class="check"><input type="checkbox" name="mail" value="1" <?=isset($_GET['mail'])?'checked':''?>>Visa endast med mejl att kontrollera</label><button>Filtrera</button></form>
<?php foreach($rows as $o): ?><article class="panel row"><div><p class="eyebrow"><?=e($o['reference'])?> · <?=e($o['created_at'])?></p><h2><a href="/admin/order.php?id=<?=$o['id']?>"><?=e($o['product_name'])?></a></h2><p><?=e($o['name'])?> · <?=money($o['price_cents'])?></p></div><?=badge($o['status'])?><a class="button secondary" href="/admin/order.php?id=<?=$o['id']?>">Öppna förfrågan</a></article><?php endforeach; if(!$rows): ?><div class="empty"><h2>Inga förfrågningar här</h2><p>Nya förfrågningar visas här när en kund har skickat formuläret.</p></div><?php endif ?>
<div class="actions"><?php if($page>1): ?><a href="?<?=e(http_build_query(array_merge($_GET,['page'=>$page-1])))?>">Föregående</a><?php endif; if($more): ?><a href="?<?=e(http_build_query(array_merge($_GET,['page'=>$page+1])))?>">Nästa</a><?php endif ?></div><?php page_end();
