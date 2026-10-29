<?php
declare(strict_types=1);
const ROOT = __DIR__ . '/..';
const REQUEST_NOTICE = 'Detta är en beställningsförfrågan. Vi kontrollerar tillgänglighet och återkommer med totalpris inklusive frakt och betalningsuppgifter. Betala först när du har fått vår bekräftelse.';
ini_set('display_errors', '0');
date_default_timezone_set('Europe/Stockholm');
set_exception_handler(function (Throwable $e): void {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
    $incident = bin2hex(random_bytes(6));
    error_log('TUK incident ' . $incident . ' type=' . get_class($e));
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Operationen misslyckades. Händelse: $incident\n"); exit(1); }
    $expected = $e instanceof DomainException;
    http_response_code($expected ? 422 : 503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="sv"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TUK-KNIVES</title><body><h1>' . ($expected ? 'Kontrollera uppgifterna' : 'Tjänsten är tillfälligt otillgänglig') . '</h1><p>' . htmlspecialchars($expected ? $e->getMessage() : 'Försök igen senare. Händelse: ' . $incident, ENT_QUOTES, 'UTF-8') . '</p><p>Gå tillbaka för att kontrollera formuläret. Skickade du en förfrågan kan du skicka samma formulär igen utan att en dubblett skapas.</p><a href="/catalog.php">Till sortimentet</a></body></html>';
});
$configPath = getenv('TUK_CONFIG') ?: dirname(ROOT) . '/tuk-config.php';
// ROOT contains /..; resolve parent explicitly to stay outside document root.
if (!getenv('TUK_CONFIG')) $configPath = dirname(realpath(ROOT)) . '/tuk-config.php';
if (!is_file($configPath)) throw new RuntimeException('Missing server configuration');
$config = require $configPath;
if (!is_array($config) || !preg_match('/^[a-f0-9]{64}$/i', $config['app_key'] ?? '')) throw new RuntimeException('Invalid configuration');
if (PHP_SAPI !== 'cli') {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($config['force_https'] && !$https) {
        header('Location: ' . rtrim($config['base_url'], '/') . '/', true, 303); exit;
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' blob:; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    if ($https) header('Strict-Transport-Security: max-age=31536000');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('tuk_session');
    session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>$https, 'httponly'=>true, 'samesite'=>'Lax']);
    session_start();
    if (isset($_SESSION['user_id']) && time() - ($_SESSION['last_seen'] ?? 0) > 1800) unset($_SESSION['user_id']);
    $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function db(): PDO {
    global $pdo, $config;
    if (!isset($pdo)) $pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
    return $pdo;
}
function query(string $sql, array $args = []): PDOStatement { $q = db()->prepare($sql); $q->execute($args); return $q; }
function one(string $sql, array $args = []): ?array { return query($sql,$args)->fetch() ?: null; }
function transaction(callable $fn): mixed {
    db()->beginTransaction();
    try { $result = $fn(); db()->commit(); return $result; }
    catch (Throwable $e) { if (db()->inTransaction()) db()->rollBack(); throw $e; }
}
function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function admin(): bool { return !empty($_SESSION['user_id']); }
function require_admin(): void { if (!admin()) redirect('/admin/login.php'); }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { http_response_code(403); exit('Ogiltig formulärsession. Ladda om sidan.'); }
}
function value(array $data, string $key, int $max, bool $required = true): string {
    $v = $data[$key] ?? '';
    if (!is_string($v)) throw new DomainException('Ogiltigt fält: '.$key);
    $v = trim($v);
    if (($required && $v === '') || mb_strlen($v) > $max || str_contains($v, "\0")) throw new DomainException('Kontrollera fältet '.$key.' (högst '.$max.' tecken).');
    return $v;
}
function email_value(array $data, string $key): string { $v=value($data,$key,190); if (!filter_var($v,FILTER_VALIDATE_EMAIL)) throw new DomainException('Ange en giltig e-postadress.'); return $v; }
function money_parse(string $v): int {
    $v = str_replace(',', '.', trim($v));
    if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D',$v)) throw new DomainException('Ange ett belopp mellan 0 och 9 999 999,99 kr med högst två decimaler.');
    $parts=explode('.',$v); return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
}
function money(int|string|null $cents): string { return $cents === null ? 'Ej fastställt' : number_format((int)$cents/100,2,',',' ').' kr'; }
function decimal(int|string|null $cents): string { return $cents === null ? '' : number_format((int)$cents/100,2,'.',''); }
function setting(string $name, string $default = ''): string { return (one('SELECT value FROM settings WHERE name=?',[$name])['value'] ?? $default); }
function rate_limit(string $scope, int $max, int $seconds): void {
    global $config;
    $bucket=hash_hmac('sha256',$scope,$config['app_key']); $now=time();
    transaction(function() use($bucket,$max,$seconds,$now) {
        query('INSERT INTO rate_limits (bucket,hits,expires_at) VALUES (?,0,?) ON DUPLICATE KEY UPDATE bucket=bucket',[$bucket,$now+$seconds]);
        $r=one('SELECT * FROM rate_limits WHERE bucket=? FOR UPDATE',[$bucket]);
        $hits=(int)$r['expires_at'] <= $now ? 0 : (int)$r['hits'];
        if ($hits >= $max) throw new DomainException('För många försök. Vänta en stund och försök igen.');
        query('UPDATE rate_limits SET hits=?,expires_at=? WHERE bucket=?',[$hits+1, (int)$r['expires_at'] <= $now ? $now+$seconds : $r['expires_at'],$bucket]);
    });
    query('DELETE FROM rate_limits WHERE expires_at < ? LIMIT 100',[$now-86400]);
}
function labels(): array { return ['available'=>'Tillgänglig','reserved'=>'Reserverad','sold'=>'Såld','draft'=>'Utkast','published'=>'Publicerad','archived'=>'Arkiverad','new'=>'Ny','confirmed'=>'Bekräftad','awaiting_payment'=>'Inväntar betalning','paid'=>'Betald','shipped'=>'Skickad','cancelled'=>'Avbruten','pending'=>'Väntar på utskick','sending'=>'Leverans pågår eller behöver kontrolleras','sent'=>'Skickat','failed'=>'Misslyckat']; }
function badge(string $s): string { return '<span class="badge '.e($s).'">'.e(labels()[$s]??$s).'</span>'; }
function product(int $id, bool $lock=false): array { $p=one('SELECT * FROM products WHERE id=?'.($lock?' FOR UPDATE':''),[$id]); if (!$p) throw new DomainException('Produkten finns inte.'); return $p; }
function visible_product(array $p): bool { return !$p['deleted_at'] && $p['publication']==='published' && ($p['availability']!=='sold' || setting('show_sold')==='1'); }
function images(int $id): array { return query('SELECT * FROM product_images WHERE product_id=? ORDER BY position,id',[$id])->fetchAll(); }
function image_url(string $filename): string { return '/uploads/'.rawurlencode($filename); }
function event(int $id,string $text): void { query('INSERT INTO order_events (order_id,actor_id,description) VALUES (?,?,?)',[$id,$_SESSION['user_id']??null,$text]); }
require __DIR__.'/mail.php';
require __DIR__.'/services.php';
require __DIR__.'/view.php';
