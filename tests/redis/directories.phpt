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
Assert::contains('.', $files);
Assert::contains(basename($testfile), $files);
rewinddir($res);
Assert::same('.', readdir($res));

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

$context = stream_context_create(array('dir' => array('recursive' => true)));
Assert::true(rmdir('redis://', $context));

Assert::true(mkdir('redis://a/aa/aaa/',0700, true));
Assert::true(mkdir('redis://a/bb/aaa/',0700, true));
Assert::true(mkdir('redis://a/bb/bbb/',0700, true));
Assert::true(mkdir('redis://a/cc/aaa/',0700, true));
Assert::true(mkdir('redis://b/aa/aaa/',0700, true));
Assert::true(mkdir('redis://b/bb/aaa/',0700, true));
Assert::true(touch('redis://a/pokus.txt'));

$path = 'redis://a';
$handle = new \RecursiveDirectoryIterator($path);
$files = [];
foreach($handle as $file => $info) {
	$files[] = $info->getFilename();
}
Assert::same(['.', '..', 'aa', 'bb', 'cc', 'pokus.txt'], $files);

$path = 'redis://';
$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
$files = [];
foreach($iterator as $name => $object){
	$files[] = $name;
}
Assert::count(28, $files);
Assert::true(in_array('redis://a/pokus.txt', $files));
Assert::true(in_array('redis://a/.', $files));
Assert::true(in_array('redis://a/..', $files));
