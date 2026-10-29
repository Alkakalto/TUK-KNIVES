<?php
require __DIR__.'/../app/bootstrap.php'; require_admin();
$id=(int)($_GET['id']??0); $error=null;
if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $action=value($_POST,'action',30);
        if($action==='save') {
            if(!$id && empty($_SESSION['product_forms'][$_POST['product_token']??''])) throw new DomainException('Formuläret har gått ut. Öppna Lägg till produkt igen.');
            $id=save_product($id,$_POST,$_FILES['images']??[]); $_SESSION['flash']='Produkten är sparad. Förhandsgranska gärna innan du publicerar.';
        }
        else {
            if($action==='delete' && ($_POST['confirm_delete']??'')!=='1') throw new DomainException('Bekräfta borttagningen genom att kryssa i rutan.');
            $id=product_action($id,$action,(int)($_POST['version']??0)); $_SESSION['flash']= $action==='duplicate'?'Kopian är sparad som utkast med nytt produktnummer.':'Ändringen är sparad.';
            if($action==='delete') redirect('/admin/');
        }
        redirect('/admin/product.php?id='.$id);
    } catch(DomainException $e) { $error=$e->getMessage(); }
}
$p=$id?product($id):['number'=>'Skapas automatiskt när du sparar','name'=>'','description'=>'','price_cents'=>0,'category'=>'','material'=>'','dimensions'=>'','publication'=>'draft','availability'=>'available','version'=>0];
if(!empty($p['deleted_at'])) throw new DomainException('Produkten har tagits bort.');
$form=($error && ($_POST['action']??'')==='save')?array_merge($p,$_POST):$p;
$productToken=$_POST['product_token']??bin2hex(random_bytes(32));
if(!$id) {
    $_SESSION['product_forms']??=[];
    if(count($_SESSION['product_forms'])>20) array_shift($_SESSION['product_forms']);
    $_SESSION['product_forms'][$productToken]=true;
}
$ims=$id?images($id):[]; page_start($id?'Redigera produkt':'Lägg till produkt'); form_error($error); ?>
<p class="eyebrow">Produktnummer: <?=e($p['number'])?></p><div class="actions"><a href="/admin/">← Alla produkter</a><?php if($id): ?><a class="button secondary" href="/product.php?id=<?=$id?>" target="_blank" rel="noopener">Förhandsgranska sparad produkt</a><?php endif ?></div>
<form method="post" enctype="multipart/form-data" class="panel"><?=csrf()?><input type="hidden" name="product_token" value="<?=e($productToken)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="version" value="<?=e($p['version'])?>"><div class="form-grid">
<?php field('name','Namn',$form['name'],'text',true,160); field('price','Pris i SEK',$form['price']??decimal($p['price_cents']),'text',true,20); field('category','Kategori',$form['category'],'text',true,100); select_field('publication','Publicering',['draft'=>'Utkast','published'=>'Publicerad','archived'=>'Arkiverad'],$form['publication']); select_field('availability','Tillgänglighet',['available'=>'Tillgänglig','reserved'=>'Reserverad','sold'=>'Såld'],$form['availability']); field('material','Material',$form['material'],'text',true,500); field('dimensions','Mått',$form['dimensions'],'text',true,500); ?></div><?php textarea('description','Beskrivning',$form['description'],10000,true); ?>
<h2>Bilder</h2><p>JPG, PNG eller WebP. Högst 12 bilder, 8 MB per bild. Första bilden blir huvudbild. Spara uppladdningen, välj sedan huvudbild och ordning.</p><label>Ladda upp flera bilder<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple id="image-upload"></label><div id="upload-preview" class="image-editor" aria-live="polite"></div>
<div class="image-editor"><?php foreach($ims as $index=>$im): ?><div class="image-tile"><img src="<?=e(image_url($im['filename']))?>" alt="Produktbild <?=$index+1?>"><label class="check"><input type="radio" name="main_image" value="<?=$im['id']?>" <?=$index===0?'checked':''?>>Huvudbild</label><label>Ordning<input type="number" min="0" max="999" name="image_position[<?=$im['id']?>]" value="<?=e($im['position'])?>"></label><label class="check"><input type="checkbox" name="remove_images[]" value="<?=$im['id']?>">Ta bort bilden</label></div><?php endforeach ?></div>
<p class="muted">Utkast syns bara för dig. Reservera en kniv för en viss kund via förfrågan, så kopplas reservationen till rätt person.</p><button>Spara produkt</button></form>
<?php if($id): ?><section class="panel"><h2>Fler åtgärder</h2><form method="post" class="actions"><?=csrf()?><input type="hidden" name="version" value="<?=e($p['version'])?>"><button name="action" value="duplicate" class="secondary">Duplicera till nytt utkast</button><button name="action" value="<?=$p['publication']==='archived'?'restore':'archive'?>" class="secondary"><?=$p['publication']==='archived'?'Återställ till utkast':'Arkivera produkt'?></button></form><details><summary>Ta bort produkt</summary><form method="post"><?=csrf()?><input type="hidden" name="version" value="<?=e($p['version'])?>"><p>Produkten tas bort från katalog och produktlista. Tidigare förfrågningar och deras produktuppgifter bevaras. Använd Arkivera om du vill kunna återställa produkten.</p><label class="check"><input type="checkbox" name="confirm_delete" value="1" required>Jag vill ta bort <?=e($p['name'])?>.</label><button name="action" value="delete" class="danger">Ta bort produkt</button></form></details></section><?php endif; page_end();
