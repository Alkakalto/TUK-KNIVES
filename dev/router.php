<?php
declare(strict_types=1);
// PHP's development server does not read .htaccess. Never use it in production.
$root=realpath(__DIR__.'/..');
$path=str_replace('\\','/',rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/'));
if(preg_match('~(^|/)\.|^/(app|database|bin|tests|docs|dev|vendor|Schedual)(/|$)|^/config.*\.php$|\.(sql|log|ini|bak|md|lock|zip)$~i',$path)) {
    http_response_code(403); exit('Åtkomst nekad.');
}
if(str_starts_with($path,'/uploads/') && !preg_match('~^/uploads/[a-f0-9]{48}\.(jpg|png|webp)$~D',$path)) { http_response_code(404); exit; }
$file=realpath($root.$path);
if($file===false || ($file!==$root && !str_starts_with($file,$root.DIRECTORY_SEPARATOR))) { http_response_code(404); exit('Sidan finns inte.'); }
return false;
