<?php
require __DIR__.'/../app/bootstrap.php';
if(admin()) redirect('/admin/'); $error=null;
if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $email=strtolower(email_value($_POST,'email')); $password=$_POST['password']??'';
        if(!is_string($password) || strlen($password)>200) throw new DomainException('Fel e-postadress eller lösenord.');
        rate_limit('login-ip:'.($_SERVER['REMOTE_ADDR']??''),20,900);
        rate_limit('login-account:'.$email,8,900);
        $user=one('SELECT * FROM users WHERE email=?',[$email]);
        $valid=password_verify($password,$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if(!$user || !$valid) throw new DomainException('Fel e-postadress eller lösenord.');
        session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['last_seen']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32));
        if(password_needs_rehash($user['password_hash'],PASSWORD_DEFAULT)) query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$user['id']]);
        redirect('/admin/');
    } catch(DomainException $e) { $error=$e->getMessage(); }
}
page_start('Ägarinloggning'); form_error($error); ?>
<form method="post" class="panel narrow"><?=csrf()?><p>Logga in för att hantera knivar och förfrågningar.</p><label>E-postadress<input type="email" name="email" autocomplete="username" required maxlength="190" value="<?=e($_POST['email']??'')?>"></label><label>Lösenord<input type="password" name="password" autocomplete="current-password" required maxlength="200"></label><button>Logga in</button></form><?php page_end();
