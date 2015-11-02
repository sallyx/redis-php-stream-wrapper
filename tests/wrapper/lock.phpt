<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\RedisWrapper;
use Sallyx\StreamWrappers\DefaultRedisConnector;

$connector = new DefaultRedisConnector;

Assert::true(RedisWrapper::register('redis', $connector));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://lock/', $context);
$lockFile = 'redis://lock/lockfile.txt';
Assert::same(11, file_put_contents($lockFile,'lorem ipsum'));

$fs1 = fopen($lockFile,'r+');
Assert::true($fs1 !== NULL);
$fs2 = fopen($lockFile,'r+');
Assert::true($fs2 !== NULL);
$fs3 = fopen($lockFile,'r+');
Assert::true($fs3 !== NULL);

Assert::false(flock($fs1, LOCK_UN | LOCK_NB));
Assert::true(flock($fs2, LOCK_SH));
Assert::false(flock($fs3, LOCK_EX | LOCK_NB));
Assert::true(flock($fs2, LOCK_UN));
Assert::true(flock($fs1, LOCK_EX));
Assert::true(flock($fs1, LOCK_UN));
Assert::true(flock($fs3, LOCK_EX));
Assert::true(flock($fs3, LOCK_SH));
Assert::true(flock($fs3, LOCK_UN));
Assert::true(fclose($fs1));
Assert::true(fclose($fs2));
Assert::true(fclose($fs3));
