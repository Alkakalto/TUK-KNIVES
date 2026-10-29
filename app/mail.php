<?php
declare(strict_types=1);
function queue_mail(int $orderId, string $kind, string $recipient, string $subject, string $body): int {
    query('INSERT INTO mail_outbox (order_id,kind,recipient,subject,body) VALUES (?,?,?,?,?)',[$orderId,$kind,$recipient,$subject,$body]);
    return (int)db()->lastInsertId();
}
function dispatch_mail(int $id): void {
    global $config;
    // Atomic claim: parallel workers cannot send the same pending message.
    if (!query("UPDATE mail_outbox SET state='sending',attempts=attempts+1,attempted_at=NOW(),error_code=NULL WHERE id=? AND state='pending'",[$id])->rowCount()) return;
    $m=one('SELECT * FROM mail_outbox WHERE id=?',[$id]);
    db()->beginTransaction();
    try {
        // Coordinate payment dispatch with cancellation using the same product/order lock order.
        $snapshot=one('SELECT product_id FROM orders WHERE id=?',[$m['order_id']]);
        product((int)$snapshot['product_id'],true);
        $order=one('SELECT status FROM orders WHERE id=? FOR UPDATE',[$m['order_id']]);
        if($m['kind']==='payment' && $order['status']!=='awaiting_payment') {
            query("UPDATE mail_outbox SET state='failed',error_code='ORDER_CANCELLED' WHERE id=?",[$id]);
            db()->commit(); return;
        }
        if($m['kind']==='owner') {
            $m['recipient']=setting('owner_email');
            query('UPDATE mail_outbox SET recipient=? WHERE id=?',[$m['recipient'],$id]);
        }
        foreach (['Exception','PHPMailer','SMTP'] as $class) require_once ROOT.'/vendor/PHPMailer/src/'.$class.'.php';
        $s=$config['smtp'];
        if (!in_array($s['encryption'],['tls','ssl'],true)) throw new RuntimeException('TLS required');
        $mailer=new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP(); $mailer->Host=$s['host']; $mailer->Port=(int)$s['port'];
        $mailer->SMTPAuth=true; $mailer->Username=$s['username']; $mailer->Password=$s['password'];
        $mailer->SMTPSecure=$s['encryption']; $mailer->Timeout=10; $mailer->CharSet='UTF-8';
        $mailer->setFrom($s['from_email'],$s['from_name']); $mailer->addAddress($m['recipient']);
        $mailer->Subject=$m['subject']; $mailer->Body=$m['body'];
        // Stable ID assists tracing uncertain SMTP delivery; SMTP cannot guarantee exactly once.
        $host=parse_url($config['base_url'],PHP_URL_HOST) ?: 'localhost';
        $mailer->MessageID='<tuk-'.$id.'-'.$m['order_id'].'@'.$host.'>';
        $mailer->send();
        query("UPDATE mail_outbox SET state='sent',sent_at=NOW() WHERE id=?",[$id]);
        db()->commit();
    } catch (Throwable $e) {
        if(db()->inTransaction()) db()->rollBack();
        query("UPDATE mail_outbox SET state='failed',error_code='SMTP_DELIVERY_FAILED' WHERE id=?",[$id]);
        error_log('TUK SMTP delivery failed, message id='.$id);
    }
}
function dispatch_order_mail(int $id): void { foreach(query("SELECT id FROM mail_outbox WHERE order_id=? AND state='pending' ORDER BY id",[$id]) as $m) dispatch_mail((int)$m['id']); }
function payment_preview(array $o): string {
    if ($o['total_cents']===null) throw new DomainException('Bekräfta förfrågan och ange frakt först.');
    $body='Hej '.$o['name']."!\n\nVi bekräftar din beställning av ".$o['product_name'].' ('.$o['product_number'].").\nReferens: ".$o['reference']."\nProdukt: ".money($o['price_cents'])."\nFrakt: ".money($o['shipping_cents'])."\nTotalt: ".money($o['total_cents'])."\n\n";
    if ($o['payment_method']==='swish') {
        $number=setting('swish_number'); $recipient=setting('swish_recipient');
        if (!$number || !$recipient) throw new DomainException('Fyll i Swish-nummer och mottagare under Inställningar först.');
        $body.='Swisha till '.$number.' ('.$recipient."). Ange referensen ".$o['reference'].".\n";
    } else $body.="Betalning enligt överenskommelse.\n";
    return $body.$o['payment_instruction']."\n\n".setting('shipping_text')."\n\n".setting('shop_name')."\n".setting('contact_email');
}
