<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\RedisWrapper;
use Sallyx\StreamWrappers\DefaultRedisConnector;

$connector = new DefaultRedisConnector;

Assert::true(RedisWrapper::register('redis', $connector));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://sallyx/', $context);

$res = fopen('redis://sallyx/pokus.txt','wb', true);
Assert::true($res !== NULL);
$str = 'Hello World ';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
$len = fwrite($res, $str, 5);
Assert::same(5, $len);
Assert::true(fclose($res));

$res = fopen('redis://sallyx/pokus.txt', 'r');
Assert::true($res !== NULL);
$readed = fread($res, 12);
Assert::same(12, strlen($readed));
Assert::same('Hello World ', $readed);
$readed = fread($res, 100);
Assert::same(5, strlen($readed));
Assert::same('Hello', $readed);
Assert::true(fclose($res));

Assert::error(function() {
	$res = fopen('redis://sallyx/not-exists.txt', 'r');
}, E_WARNING);

$res = fopen('redis://sallyx/pokus.txt','w+');
Assert::true($res !== NULL);
$str = 'Hello World';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
Assert::true(fclose($res));
$content = file_get_contents('redis://sallyx/pokus.txt');
Assert::same($str, $content);

$res = fopen('redis://sallyx/pokus.txt','w+');
Assert::true($res !== NULL);
$str = 'Hello World';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
Assert::true(fclose($res));
$content = file_get_contents('redis://sallyx/pokus.txt');
Assert::same($str, $content);


$res = fopen('redis://sallyx/pokus.txt','r');
Assert::true($res !== NULL);
$ret = fwrite($res,'lorem ipsum');
Assert::same(0, $ret);
Assert::true(fclose($res));
$content = file_get_contents('redis://sallyx/pokus.txt');
Assert::same($str, $content);

$res = fopen('redis://sallyx/pokus.txt','a');
Assert::true($res !== NULL);
Assert::same(0, fseek($res,  0, SEEK_SET));
$readed = fread($res, 1000);
Assert::same('', $readed);
Assert::true(fclose($res));

$res = fopen('redis://sallyx/pokus.txt','a+');
Assert::true($res !== NULL);
Assert::same(0, fseek($res,  -10, SEEK_END));
$readed = fread($res, 5);
Assert::same('ello ', $readed);
$ret = fwrite($res, 'END');
Assert::same(3, $ret);
Assert::true(fclose($res));
$content = file_get_contents('redis://sallyx/pokus.txt');
Assert::same($str.'END', $content);

$fileToRemove = 'redis://sallyx/fileToRemove.txt';
@unlink($fileToRemove); // $fileToRemove might or might not exists
Assert::false(file_exists($fileToRemove));
$res = fopen($fileToRemove,'w');
Assert::true($res !== NULL);
Assert::true(fclose($res));
clearstatcache ();
Assert::true(file_exists($fileToRemove));
unlink($fileToRemove);
clearstatcache ();
Assert::false(file_exists($fileToRemove));
Assert::error(function() {
	unlink('redis://sallyx/');
}, E_USER_WARNING);

Assert::error(function() {
	$res = fopen('redis://sallyx/pokus.txt','x');
}, E_WARNING);

$fileToCreate = 'redis://sallyx/fileToCreate.txt';
@unlink($fileToCreate);
$res = fopen($fileToCreate, 'x');
Assert::true($res !== NULL);
Assert::true(fclose($res));

$res = fopen($fileToCreate, 'c');
Assert::true($res !== NULL);
Assert::true(fclose($res));
unlink($fileToCreate);
$res = fopen($fileToCreate, 'c');
Assert::true($res !== NULL);
Assert::true(fclose($res));

Assert::error(function() {
	$res = fopen('redis://sallyx/pokus.txt','@');
}, E_WARNING);

$fileToTruncate = 'redis://sallyx/fileToTruncate.txt';
file_put_contents($fileToTruncate, 'Hello World');
$res = fopen($fileToTruncate, 'r+');
Assert::true($res !== NULL);
Assert::true(ftruncate($res, 5));
Assert::true(fclose($res));
Assert::same('Hello', file_get_contents($fileToTruncate));
$res = fopen($fileToTruncate, 'r+');
Assert::true($res !== NULL);
Assert::true(ftruncate($res, 10));
Assert::true(fclose($res));
Assert::same(10, strlen(file_get_contents($fileToTruncate)));


//DIRECTORIES
$res = opendir('redis://sallyx/');
Assert::true($res !== NULL);
closedir($res);

$res = opendir('redis://sallyx/');
Assert::true($res !== NULL);
$files = [];
while (false !== ($file = readdir($res))) {
	$files[] = $file;
}
Assert::contains('redis://sallyx/', $files);
Assert::contains($fileToTruncate, $files);
rewinddir($res);
Assert::same('redis://sallyx/', readdir($res));

closedir($res);

Assert::false(mkdir($fileToTruncate));
Assert::false(mkdir('redis://sallyx/a/b/c/'));
Assert::false(file_exists('redis://sallyx/a'));
Assert::true(mkdir('redis://sallyx/a'));
Assert::true(mkdir('redis://sallyx/a/b/c',0700, true));
Assert::false(rmdir('redis://sallyx/a'));
Assert::true(rmdir('redis://sallyx/a/b/c'));
Assert::false(file_exists('redis://sallyx/a/b/c/'));
$context = stream_context_create(array('dir' => array('recursive' => true)));
Assert::true(rmdir('redis://sallyx/a', $context));
Assert::false(file_exists('redis://sallyx/a'));

$from = 'redis://sallyx/file1.txt';
$to = 'redis://sallyx/file2.txt';
@file_put_contents($from,'lorem ipsum');
@unlink($to);
Assert::false(rename($from, 'redis://sallyx/non/existing/directory/soubor.txt'));
Assert::true(rename($from, $to));
Assert::true(file_exists($to));
Assert::false(file_exists($from));
$dir = 'redis://sallyx/movehere/';
@unlink($dir);
@mkdir($dir);
Assert::true(rename($to, $dir));
Assert::true(file_exists($dir.'file2.txt'));
