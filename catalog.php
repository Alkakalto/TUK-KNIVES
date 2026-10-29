<?php
require __DIR__.'/app/bootstrap.php';
$search=value($_GET,'q',100,false); $category=value($_GET,'category',100,false);
$where="p.publication='published' AND p.deleted_at IS NULL"; $args=[];
if(setting('show_sold')!=='1') $where.=" AND p.availability<>'sold'";
if($search!=='') { $where.=' AND (p.name LIKE ? OR p.number LIKE ? OR p.description LIKE ?)'; $args=array_fill(0,3,'%'.$search.'%'); }
if($category!=='') { $where.=' AND p.category=?'; $args[]=$category; }
$page=max(1,(int)($_GET['page']??1)); $limit=24; $offset=($page-1)*$limit;
$total=(int)one('SELECT COUNT(*) AS n FROM products p WHERE '.$where,$args)['n'];
$products=query('SELECT p.*, (SELECT filename FROM product_images WHERE product_id=p.id ORDER BY position,id LIMIT 1) AS image FROM products p WHERE '.$where.' ORDER BY p.created_at DESC,p.id DESC LIMIT 24 OFFSET '.$offset,$args)->fetchAll();
$categories=query("SELECT DISTINCT category FROM products WHERE publication='published' AND deleted_at IS NULL".(setting('show_sold')==='1'?'':" AND availability<>'sold'").' ORDER BY category')->fetchAll();
page_start('Sortiment'); ?>
<p class="lede">Handgjorda knivar med egen karaktär. Hitta din kniv och skicka en beställningsförfrågan.</p>
<form class="filters" method="get"><label>Sök kniv<input type="search" name="q" value="<?=e($search)?>" placeholder="Namn, produktnummer eller beskrivning" maxlength="100"></label><label>Kategori<select name="category"><option value="">Alla kategorier</option><?php foreach($categories as $c): ?><option <?=$category===$c['category']?'selected':''?>><?=e($c['category'])?></option><?php endforeach ?></select></label><button>Visa produkter</button></form>
<p class="muted"><?=$total?> produkter</p><div class="catalog-grid">
<?php foreach($products as $p): ?><article class="product-card"><a class="product-image" href="/product.php?id=<?=$p['id']?>"><?php if($p['image']): ?><img src="<?=e(image_url($p['image']))?>" alt="<?=e($p['name'])?>" loading="lazy"><?php else: ?><span>Bild kommer</span><?php endif ?></a><div class="card-content"><?=badge($p['availability'])?><p class="eyebrow"><?=e($p['category'])?> · <?=e($p['number'])?></p><h2><a href="/product.php?id=<?=$p['id']?>"><?=e($p['name'])?></a></h2><p><?=e(mb_strimwidth($p['description'],0,140,'…'))?></p><p class="price"><?=money($p['price_cents'])?></p><a class="button secondary" href="/product.php?id=<?=$p['id']?>">Visa kniven</a><?php if(admin()): ?> <a href="/admin/product.php?id=<?=$p['id']?>">Redigera produkt</a><?php endif ?></div></article><?php endforeach ?>
</div><?php if(!$products): ?><div class="empty"><h2><?=$search||$category?'Inga knivar matchar din sökning':'Här kommer våra knivar'?></h2><p><?=$search||$category?'Prova en annan sökning eller välj alla kategorier.':'Det finns inga publicerade produkter just nu. Välkommen tillbaka!'?></p><?php if(admin()): ?><a class="button" href="/admin/product.php">Lägg till produkt</a><?php endif ?></div><?php endif ?>
<div class="actions"><?php if($page>1): ?><a href="?<?=e(http_build_query(['q'=>$search,'category'=>$category,'page'=>$page-1]))?>">Föregående sida</a><?php endif; if($offset+24<$total): ?><a href="?<?=e(http_build_query(['q'=>$search,'category'=>$category,'page'=>$page+1]))?>">Nästa sida</a><?php endif ?></div>
<?php page_end();
