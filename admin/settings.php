<?php
require __DIR__.'/../app/bootstrap.php'; require_admin(); $error=null;
$fields=['shop_name'=>'Butiksnamn','contact_email'=>'Kontaktens e-postadress','contact_phone'=>'Telefon','contact_address'=>'Adress','owner_email'=>'Ägarens notifieringsadress','swish_number'=>'Swish-nummer','swish_recipient'=>'Swish-mottagare','shipping_text'=>'Standardtext om frakt och leverans'];
if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $values=[]; foreach($fields as $k=>$label) $values[$k]=str_contains($k,'email')?email_value($_POST,$k):value($_POST,$k,$k==='shipping_text'?2000:250,!in_array($k,['swish_number','swish_recipient']));
        $values['show_sold']=isset($_POST['show_sold'])?'1':'0';
        transaction(function() use($values) { foreach($values as $k=>$v) query('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$k,$v]); });
        $_SESSION['flash']='Inställningarna är sparade.'; redirect('/admin/settings.php');
    } catch(DomainException $e) { $error=$e->getMessage(); }
}
page_start('Inställningar'); form_error($error); ?><form method="post" class="panel"><?=csrf()?><div class="form-grid"><?php foreach($fields as $k=>$label) { if($k==='shipping_text') continue; field($k,$label,$_POST[$k]??setting($k),str_contains($k,'email')?'email':'text',!in_array($k,['swish_number','swish_recipient']),250); } ?></div><?php textarea('shipping_text',$fields['shipping_text'],$_POST['shipping_text']??setting('shipping_text'),2000,true); ?><label class="check"><input type="checkbox" name="show_sold" value="1" <?=($error?isset($_POST['show_sold']):setting('show_sold')==='1')?'checked':''?>>Visa sålda knivar som tidigare arbeten i katalogen</label><p>Swish-uppgifter visas för kunden först i det betalningsmejl som du godkänner efter reservation. SMTP- och databasuppgifter ändras endast i den skyddade serverkonfigurationen.</p><button>Spara inställningar</button></form><?php page_end();
