<?php

require_once '../bootstrap.php';

use Tester\Assert;

_register_wrapper('url-stat::');

$file = 'redis://testfile.txt';
$dir = 'redis://a';

Assert::same(11, file_put_contents($file, 'lorem ipsum'));
Assert::true(mkdir($dir));
Assert::true(is_file($file));
Assert::true(is_dir($dir));
Assert::false(is_file($dir));
Assert::false(is_dir($file));
