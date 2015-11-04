<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\Redis\Connector;
use Sallyx\StreamWrappers\Redis\ConnectorConfig;
use Sallyx\StreamWrappers\Redis\PathTranslator;
use Sallyx\StreamWrappers\Redis\FileSystem;
use Sallyx\StreamWrappers\Wrapper;

$fs = new FileSystem(new Connector(new ConnectorConfig, new PathTranslator('url-stat::')));
Assert::true(Wrapper::register($fs));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://', $context);
$file = 'redis://testfile.txt';
$dir = 'redis://a';

Assert::same(11, file_put_contents($file,'lorem ipsum'));
Assert::true(mkdir($dir));
Assert::true(is_file($file));
Assert::true(is_dir($dir));
Assert::false(is_file($dir));
Assert::false(is_dir($file));
