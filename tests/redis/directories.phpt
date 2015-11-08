<?php

require_once '../bootstrap.php';

use Tester\Assert;

_register_wrapper('directories::');

$res = opendir('redis://');
Assert::true($res !== NULL);
closedir($res);

$testfile = 'redis://test.txt';

file_put_contents($testfile,'lorem ipsum');
Assert::true(mkdir('redis://x/y/z',0777, true));

$res = opendir('redis://');
Assert::true($res !== NULL);
$files = [];
while (false !== ($file = readdir($res))) {
	$files[] = $file;
}
Assert::count(3, $files);
Assert::contains('redis://', $files);
Assert::contains($testfile, $files);
rewinddir($res);
Assert::same('redis://', readdir($res));

closedir($res);

Assert::false(mkdir($testfile));
Assert::false(mkdir('redis://a/b/c/'));
Assert::false(file_exists('redis://a'));
Assert::true(mkdir('redis://a'));
Assert::true(mkdir('redis://a/b/c', 0700, true));
Assert::true(is_dir('redis://a/b/c'));
Assert::false(rmdir('redis://a'));
Assert::true(rmdir('redis://a/b/c'));
Assert::false(file_exists('redis://a/b/c/'));
$context = stream_context_create(array('dir' => array('recursive' => true)));
Assert::true(rmdir('redis://a', $context));
Assert::false(file_exists('redis://a'));
