<?php
if(PHP_SAPI!=='cli' || getenv('TUK_RUN_TESTS')!=='1') exit(1);
require __DIR__.'/../app/bootstrap.php';
if(!str_ends_with(one('SELECT DATABASE() name')['name'],'_test')) exit(1);
db()->exec(file_get_contents(__DIR__.'/../database/001_initial.sql'));
echo "Testschema importerat.\n";
