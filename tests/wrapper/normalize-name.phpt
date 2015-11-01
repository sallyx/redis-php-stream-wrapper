<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\RedisWrapper;
use Sallyx\StreamWrappers\DefaultRedisConnector;

$connector = new DefaultRedisConnector;

Assert::true(RedisWrapper::register('redis', $connector));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://sallyx2/', $context);

Assert::true(mkdir('redis://sallyx2/a/b//c/../../d///e/',0777, true));
Assert::true(file_exists('redis://sallyx2/a/d/e'));
