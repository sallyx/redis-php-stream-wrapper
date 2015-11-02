<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\RedisWrapper;
use Sallyx\StreamWrappers\DefaultRedisConnector;

$connector = new DefaultRedisConnector;

Assert::true(RedisWrapper::register('redis', $connector));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://url-stat/', $context);
$file = 'redis://url-stat/testfile.txt';
$dir = 'redis://url-stat/a';

Assert::same(11, file_put_contents($file,'lorem ipsum'));
Assert::true(mkdir($dir));
Assert::true(is_file($file));
Assert::true(is_dir($dir));
Assert::false(is_file($dir));
Assert::false(is_dir($file));
