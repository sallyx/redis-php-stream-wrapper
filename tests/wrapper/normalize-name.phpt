<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\Redis\Connector;
use Sallyx\StreamWrappers\Redis\PathTranslator;
use Sallyx\StreamWrappers\Redis\FileSystem;
use Sallyx\StreamWrappers\Wrapper;

$fs = new FileSystem(new Connector(new PathTranslator('normalize-name::')));
Assert::true(Wrapper::register($fs));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://', $context);

Assert::true(mkdir('redis://a/b//c/../../d///e/',0777, true));
Assert::true(file_exists('redis://a/d/e'));
