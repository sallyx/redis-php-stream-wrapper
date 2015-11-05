<?php

require_once '../bootstrap.php';

use Tester\Assert;

_register_wrapper('normalize-name::');

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://', $context);

Assert::true(mkdir('redis://a/b//c/../../d///e/', 0777, true));
Assert::true(file_exists('redis://a/d/e'));
