<?php
require __DIR__.'/app/bootstrap.php';
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $token=value($_POST,'submission',64);
        $entry=$_SESSION['submissions'][$token]??null;
        if(!$entry || $entry['product']!==(int)($_POST['product_id']??0)) throw new DomainException('Formuläret har gått ut. Öppna produktsidan och börja igen.');
        if(!one('SELECT id FROM orders WHERE submission_key=?',[hash('sha256',$token)])) {
            if(value($_POST,'website',200,false)!=='' || time()-$entry['time']<3) throw new DomainException('Kontrollera formuläret och försök igen om några sekunder.');
            rate_limit('request-ip:'.($_SERVER['REMOTE_ADDR']??''),8,3600);
            rate_limit('request-email:'.strtolower(email_value($_POST,'email')),5,3600);
        }
        $id=submit_request($_POST,hash('sha256',$token));
        $reference=one('SELECT reference FROM orders WHERE id=?',[$id])['reference'];
        $_SESSION['receipts'][$reference]=true;
        // Commit and redirect before SMTP. A separate cron or receipt dispatch cannot lose the request.
        redirect('/receipt.php?ref='.urlencode($reference));
    } catch(DomainException $e) { $error=$e->getMessage(); }
}
$p=product((int)($_POST['product_id']??$_GET['product']??0));
if(!visible_product($p) || $p['availability']!=='available') throw new DomainException('Denna kniv är reserverad, såld eller inte tillgänglig.');
if(!isset($token) || !isset($_SESSION['submissions'][$token])) {
    $token=bin2hex(random_bytes(32));
    $_SESSION['submissions']??=[];
    if(count($_SESSION['submissions'])>20) array_shift($_SESSION['submissions']);
    $_SESSION['submissions'][$token]=['product'=>(int)$p['id'],'time'=>time()];
}
page_start('Skicka beställningsförfrågan'); form_error($error); ?>
<form method="post" class="panel request-form"><?=csrf()?><input type="hidden" name="submission" value="<?=e($token)?>"><input type="hidden" name="product_id" value="<?=$p['id']?>"><div class="notice"><strong><?=e($p['name'])?> · <?=e($p['number'])?></strong><p><?=money($p['price_cents'])?>, frakt tillkommer efter överenskommelse.</p></div><div class="form-grid">
<?php field('name','Namn',$_POST['name']??'','text',true,160); field('email','E-postadress',$_POST['email']??'','email',true,190); field('phone','Telefonnummer (valfritt)',$_POST['phone']??'','tel',false,50); field('address','Leveransadress',$_POST['address']??'','text',true,250); field('postcode','Postnummer',$_POST['postcode']??'','text',true,30); field('city','Ort',$_POST['city']??'','text',true,100); field('country','Land (skriv Sverige för Swish)',$_POST['country']??'Sverige','text',true,100); ?>
<label>Önskat betalningssätt<input id="payment-method" readonly value="Swish för Sverige; annars betalning enligt överenskommelse"><small>Bestäms av leveranslandet. Betala först efter vår bekräftelse.</small></label></div>
<?php textarea('message','Meddelande (valfritt)',$_POST['message']??'',4000); ?><div class="honeypot" aria-hidden="true"><label>Lämna detta fält tomt<input name="website" tabindex="-1" autocomplete="off"></label></div><p class="notice"><?=e(REQUEST_NOTICE)?></p><p>Vi använder dina kontakt- och leveransuppgifter för att hantera din förfrågan. Läs om hanteringen på vår <a href="/privacy.php" target="_blank" rel="noopener">integritetssida</a>.</p><button>Skicka beställningsförfrågan</button></form><?php page_end();
