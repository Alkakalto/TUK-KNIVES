<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
$root=dirname(__DIR__); $errors=0; $count=0;
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
    $relative=substr($file->getPathname(),strlen($root)+1);
    if(preg_match('~^(\.git|uploads)[/\\\\]~',$relative) || $file->getExtension()!=='php') continue;
    $command=escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname());
    passthru($command,$status); $count++; if($status!==0) $errors++;
}
echo "$count PHP-filer kontrollerade, $errors fel.\n";
exit($errors?1:0);
