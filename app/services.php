<?php
declare(strict_types=1);
function submit_request(array $data, string $key): int {
    // Lookup before availability checks allows an identical POST to retrieve its existing receipt.
    if ($existing=one('SELECT id FROM orders WHERE submission_key=?',[$key])) return (int)$existing['id'];
    $fields=[];
    foreach (['name'=>160,'phone'=>50,'address'=>250,'postcode'=>30,'city'=>100,'country'=>100,'message'=>4000] as $k=>$max) $fields[$k]=value($data,$k,$max,!in_array($k,['phone','message']));
    $fields['email']=email_value($data,'email');
    $fields['payment_method']=mb_strtolower($fields['country'])==='sverige' ? 'swish' : 'agreement';
    return transaction(function() use($data,$key,$fields) {
        $p=product((int)($data['product_id']??0),true);
        if ($existing=one('SELECT id FROM orders WHERE submission_key=?',[$key])) return (int)$existing['id'];
        if (!visible_product($p) || $p['availability']!=='available') throw new DomainException('Den här kniven kan inte beställas just nu.');
        $ref='TUK-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(6)));
        query('INSERT INTO orders (reference,submission_key,product_id,product_number,product_name,price_cents,name,email,phone,address,postcode,city,country,payment_method,message,payment_instruction,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$ref,$key,$p['id'],$p['number'],$p['name'],$p['price_cents'],$fields['name'],$fields['email'],$fields['phone'],$fields['address'],$fields['postcode'],$fields['city'],$fields['country'],$fields['payment_method'],$fields['message'],'','']);
        $id=(int)db()->lastInsertId(); event($id,'Ny beställningsförfrågan sparad.');
        $summary=$p['name'].' ('.$p['number'].")\nPris vid förfrågan: ".money($p['price_cents'])."\nReferens: $ref\n\n".REQUEST_NOTICE;
        queue_mail($id,'owner',setting('owner_email'),'Ny förfrågan '.$ref,$summary."\n\nÖppna ägarpanelen för kunduppgifter.");
        queue_mail($id,'receipt',$fields['email'],'Vi har tagit emot din förfrågan '.$ref,'Hej '.$fields['name']."!\n\n".$summary);
        return $id;
    });
}
function change_order(int $id,array $data): void {
    $initial=one('SELECT product_id FROM orders WHERE id=?',[$id]);
    if (!$initial) throw new DomainException('Förfrågan finns inte.');
    transaction(function() use($initial,$id,$data) {
        // All paths lock product before order. Unique active_product_id adds a second invariant.
        $p=product((int)$initial['product_id'],true);
        $o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
        if ((int)$o['version'] !== (int)($data['version']??0)) throw new DomainException('Förfrågan har ändrats i en annan flik. Ladda om innan du fortsätter.');
        $action=value($data,'action',40);
        $status=$o['status'];
        if ($action==='save') {
            $notes=value($data,'notes',10000,false); $instruction=value($data,'payment_instruction',5000,false);
            $shipping=$o['shipping_cents']; $total=$o['total_cents'];
            if (in_array($status,['new','confirmed'],true)) {
                $shipping=money_parse(value($data,'shipping',20)); $total=(int)$o['price_cents']+$shipping;
            }
            query('UPDATE orders SET notes=?,payment_instruction=?,shipping_cents=?,total_cents=?,version=version+1 WHERE id=?',[$notes,$instruction,$shipping,$total,$id]);
            event($id,'Anteckningar/betalningsunderlag sparat.'); return;
        }
        if ($action==='confirm') {
            if ($status!=='new' || $p['availability']!=='available' || $p['deleted_at'] || $p['publication']!=='published') throw new DomainException('Kniven kan inte reserveras. Den är redan reserverad, såld eller inte publicerad.');
            $shipping=money_parse(value($data,'shipping',20));
            query("UPDATE orders SET active_product_id=product_id,status='confirmed',shipping_cents=?,total_cents=?,version=version+1 WHERE id=?",[$shipping,(int)$o['price_cents']+$shipping,$id]);
            query("UPDATE products SET availability='reserved',version=version+1 WHERE id=?",[$p['id']]);
            event($id,'Bekräftad. Kniven reserverad för denna förfrågan.'); return;
        }
        if ($action==='send_payment') {
            if ($status!=='confirmed' || (int)$o['active_product_id']!==(int)$p['id']) throw new DomainException('Betalningsinstruktion kan bara skickas för en bekräftad reservation.');
            $body=payment_preview($o);
            if (!hash_equals(hash('sha256',$body), value($data,'preview_hash',64))) throw new DomainException('Betalningsunderlaget har ändrats. Förhandsgranska det igen.');
            queue_mail($id,'payment',$o['email'],'Betalningsuppgifter '.$o['reference'],$body);
            query("UPDATE orders SET status='awaiting_payment',version=version+1 WHERE id=?",[$id]);
            event($id,'Betalningsinstruktion godkänd av ägaren och lagd i mejlkön.'); return;
        }
        if ($action==='paid') {
            if ($status!=='awaiting_payment' || (int)$o['active_product_id']!==(int)$p['id'] || ($data['payment_checked']??'')!=='1') throw new DomainException('Kontrollera betalningen och bekräfta kontrollen innan du markerar betald.');
            query("UPDATE orders SET status='paid',version=version+1 WHERE id=?",[$id]);
            query("UPDATE products SET availability='sold',version=version+1 WHERE id=?",[$p['id']]);
            event($id,'Betald efter ägarens manuella kontroll. Kniven markerad som såld.'); return;
        }
        if ($action==='shipped') {
            if ($status!=='paid') throw new DomainException('Endast en betald order kan markeras som skickad.');
            query("UPDATE orders SET status='shipped',version=version+1 WHERE id=?",[$id]);
            event($id,'Markerad som skickad.'); return;
        }
        if ($action==='cancel') {
            if (in_array($status,['shipped','cancelled'],true)) throw new DomainException('Denna order kan inte avbrytas.');
            if ($status==='paid' && ($data['refund_checked']??'')!=='1') throw new DomainException('Bekräfta att eventuell återbetalning har hanterats manuellt.');
            if ($o['active_product_id']) query("UPDATE products SET availability='available',version=version+1 WHERE id=?",[$p['id']]);
            query("UPDATE orders SET status='cancelled',active_product_id=NULL,version=version+1 WHERE id=?",[$id]);
            query("UPDATE mail_outbox SET state='failed',error_code='ORDER_CANCELLED' WHERE order_id=? AND kind='payment' AND state='pending'",[$id]);
            event($id,'Avbruten. Eventuell reservation hävd.'); return;
        }
        throw new DomainException('Okänd åtgärd.');
    });
}
function store_uploads(array $files): array {
    $saved=[];
    try {
        if (!isset($files['name'])) return [];
        if (!is_array($files['name']) || count($files['name'])>12) throw new DomainException('Ladda upp högst 12 bilder åt gången.');
        foreach ($files['name'] as $i=>$unused) {
            if (($files['error'][$i]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
            if ($files['error'][$i]!==UPLOAD_ERR_OK || $files['size'][$i]>8*1024*1024) throw new DomainException('En bild kunde inte laddas upp. Maxstorlek är 8 MB per bild.');
            $temp=$files['tmp_name'][$i];
            if (!is_uploaded_file($temp)) throw new DomainException('Ogiltig uppladdning.');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($temp);
            $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
            $size=@getimagesize($temp);
            if (!$ext || !$size || $size['mime']!==$mime || $size[0]<100 || $size[1]<100 || $size[0]>6000 || $size[1]>6000 || $size[0]*$size[1]>20000000) throw new DomainException('Använd JPG, PNG eller WebP, minst 100 × 100 och högst 6 000 × 6 000 pixlar (20 megapixlar).');
            // Decode/re-encode to remove metadata and embedded non-image payloads.
            $im=match($ext) {'jpg'=>@imagecreatefromjpeg($temp),'png'=>@imagecreatefrompng($temp),'webp'=>@imagecreatefromwebp($temp)};
            if (!$im) throw new DomainException('Bildfilen kunde inte läsas.');
            imagealphablending($im,false); imagesavealpha($im,true);
            $name=bin2hex(random_bytes(24)).'.'.$ext; $path=ROOT.'/uploads/'.$name;
            $ok=match($ext) {'jpg'=>imagejpeg($im,$path,88),'png'=>imagepng($im,$path,6),'webp'=>imagewebp($im,$path,88)};
            imagedestroy($im);
            if (!$ok) throw new RuntimeException('Image write failed');
            $saved[]=$name; chmod($path,0644);
        }
        return $saved;
    } catch(Throwable $e) { foreach($saved as $name) @unlink(ROOT.'/uploads/'.$name); throw $e; }
}
function save_product(int $id,array $data,array $files): int {
    $creationKey=hash('sha256',isset($data['product_token'])?value($data,'product_token',64):bin2hex(random_bytes(32)));
    if(!$id && ($existing=one('SELECT id FROM products WHERE creation_key=?',[$creationKey]))) return (int)$existing['id'];
    $fields=[];
    foreach(['name'=>160,'description'=>10000,'category'=>100,'material'=>500,'dimensions'=>500] as $k=>$max) $fields[$k]=value($data,$k,$max);
    $price=money_parse(value($data,'price',20));
    $pub=value($data,'publication',20); $availability=value($data,'availability',20);
    if (!in_array($pub,['draft','published','archived'],true) || !in_array($availability,['available','reserved','sold'],true)) throw new DomainException('Ogiltig produktstatus.');
    $uploaded=store_uploads($files);
    try {
        return transaction(function() use($id,$data,$fields,$price,$pub,$availability,$uploaded,$creationKey) {
            if ($id) {
                $p=product($id,true);
                if ($p['deleted_at']) throw new DomainException('Produkten har tagits bort.');
                if ((int)$p['version']!==(int)($data['version']??0)) throw new DomainException('Produkten har ändrats i en annan flik. Ladda om och försök igen.');
                if ($availability!==$p['availability'] && one('SELECT id FROM orders WHERE active_product_id=?',[$id])) throw new DomainException('Ändra tillgänglighet via den reserverade ordern, så att reservationen behålls.');
                query('UPDATE products SET name=?,description=?,price_cents=?,category=?,material=?,dimensions=?,publication=?,availability=?,version=version+1 WHERE id=?',[$fields['name'],$fields['description'],$price,$fields['category'],$fields['material'],$fields['dimensions'],$pub,$availability,$id]);
            } else {
                query('INSERT INTO products (creation_key,number,name,description,price_cents,category,material,dimensions,publication,availability) VALUES (?,?,?,?,?,?,?,?,?,?)',[$creationKey,'TUK-'.strtoupper(bin2hex(random_bytes(6))),$fields['name'],$fields['description'],$price,$fields['category'],$fields['material'],$fields['dimensions'],$pub,$availability]);
                $id=(int)db()->lastInsertId();
            }
            foreach(images($id) as $im) {
                if (in_array((string)$im['id'], (array)($data['remove_images']??[]),true)) query('DELETE FROM product_images WHERE id=? AND product_id=?',[$im['id'],$id]);
                else {
                    $pos=filter_var($data['image_position'][$im['id']]??$im['position'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>999]]);
                    if ($pos===false) throw new DomainException('Bildordning måste vara ett tal mellan 0 och 999.');
                    if (($data['main_image']??'')===(string)$im['id']) $pos=0;
                    else $pos++;
                    query('UPDATE product_images SET position=? WHERE id=? AND product_id=?',[$pos,$im['id'],$id]);
                }
            }
            $count=count(images($id));
            if ($count+count($uploaded)>12) throw new DomainException('En produkt kan ha högst 12 bilder.');
            foreach($uploaded as $i=>$name) query('INSERT INTO product_images (product_id,filename,position) VALUES (?,?,?)',[$id,$name,100+$i]);
            foreach(images($id) as $position=>$im) query('UPDATE product_images SET position=? WHERE id=?',[$position,$im['id']]);
            if ($pub==='published' && count(images($id))===0) throw new DomainException('Lägg till minst en bild innan du publicerar.');
            return $id;
        });
    } catch(Throwable $e) {
        foreach($uploaded as $name) @unlink(ROOT.'/uploads/'.$name);
        if(!$id && $e instanceof PDOException && $e->getCode()==='23000' && ($existing=one('SELECT id FROM products WHERE creation_key=?',[$creationKey]))) return (int)$existing['id'];
        throw $e;
    }
}
function product_action(int $id,string $action,int $version): int {
    return transaction(function() use($id,$action,$version) {
        $p=product($id,true);
        if ($p['deleted_at'] || (int)$p['version']!==$version) throw new DomainException('Produkten har ändrats. Ladda om sidan.');
        if ($action==='duplicate') {
            query("INSERT INTO products (number,name,description,price_cents,category,material,dimensions) VALUES (?,?,?,?,?,?,?)",['TUK-'.strtoupper(bin2hex(random_bytes(6))),mb_substr($p['name'],0,150).' (kopia)',$p['description'],$p['price_cents'],$p['category'],$p['material'],$p['dimensions']]);
            $new=(int)db()->lastInsertId();
            query('INSERT INTO product_images (product_id,filename,position) SELECT ?,filename,position FROM product_images WHERE product_id=?',[$new,$id]);
            return $new;
        }
        if ($action==='delete') {
            if (one('SELECT id FROM orders WHERE active_product_id=? AND status<>?',[$id,'shipped'])) throw new DomainException('Avsluta eller avbryt den aktiva ordern innan du tar bort produkten.');
            query("UPDATE products SET deleted_at=NOW(),publication='archived',version=version+1 WHERE id=?",[$id]);
        } elseif (in_array($action,['archive','restore'],true)) query('UPDATE products SET publication=?,version=version+1 WHERE id=?',[$action==='archive'?'archived':'draft',$id]);
        else throw new DomainException('Okänd produktåtgärd.');
        return $id;
    });
}
