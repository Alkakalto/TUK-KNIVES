<?php
require __DIR__.'/app/bootstrap.php';
$p=product((int)($_GET['id']??0));
if($p['deleted_at'] || (!admin() && !visible_product($p))) { http_response_code(404); exit('Produkten är inte tillgänglig.'); }
$images=images((int)$p['id']); page_start($p['name']); ?>
<p><a href="/catalog.php">← Till sortimentet</a></p>
<?php if(admin()): ?><div class="notice actions">Ägarvy <?=badge($p['publication'])?><a class="button" href="/admin/product.php?id=<?=$p['id']?>">Redigera produkt</a></div><?php endif ?>
<div class="product-detail"><section aria-label="Bildgalleri"><?php if($images): ?><img class="main-image" id="gallery-main" src="<?=e(image_url($images[0]['filename']))?>" alt="<?=e($p['name'])?>"><div class="thumbnails"><?php foreach($images as $i=>$im): ?><a href="<?=e(image_url($im['filename']))?>" data-gallery aria-label="Visa bild <?=$i+1?>"><img src="<?=e(image_url($im['filename']))?>" alt="<?=e($p['name'])?>, bild <?=$i+1?>"></a><?php endforeach ?></div><?php else: ?><div class="empty">Ingen bild tillagd ännu.</div><?php endif ?></section><section><?=badge($p['availability'])?><p class="eyebrow"><?=e($p['number'])?> · <?=e($p['category'])?></p><p class="price"><?=money($p['price_cents'])?></p><p class="preline"><?=e($p['description'])?></p><dl><dt>Material</dt><dd><?=e($p['material'])?></dd><dt>Mått</dt><dd><?=e($p['dimensions'])?></dd></dl><p><?=e(setting('shipping_text'))?></p>
<?php if($p['availability']==='available' && visible_product($p)): ?><a class="button" href="/request.php?product=<?=$p['id']?>">Skicka beställningsförfrågan</a><?php else: ?><p class="notice">Denna kniv kan inte beställas just nu.</p><?php endif ?></section></div><?php page_end();
