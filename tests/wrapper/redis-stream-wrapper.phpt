<?php
require_once '../bootstrap.php';

use Tester\Assert;
use Sallyx\StreamWrappers\Redis\Connector;
use Sallyx\StreamWrappers\Redis\ConnectorConfig;
use Sallyx\StreamWrappers\Redis\PathTranslator;
use Sallyx\StreamWrappers\Redis\FileSystem;
use Sallyx\StreamWrappers\Wrapper;

$fs = new FileSystem(new Connector(new ConnectorConfig, new PathTranslator('test::')));
Assert::true(Wrapper::register($fs));

$context = stream_context_create(array('dir' => array('recursive' => true)));
@rmdir('redis://', $context);

$res = fopen('redis://pokus.txt','wb', true);
Assert::true($res !== NULL);
$str = 'Hello World ';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
$len = fwrite($res, $str, 5);
Assert::same(5, $len);
Assert::true(fclose($res));
$res = fopen('redis://pokus.txt', 'r');
Assert::true($res !== NULL);
$readed = fread($res, 12);
Assert::same(12, strlen($readed));
Assert::same('Hello World ', $readed);
$readed = fread($res, 100);
Assert::same(5, strlen($readed));
Assert::same('Hello', $readed);
Assert::true(fclose($res));

Assert::error(function() {
	$res = fopen('redis://not-exists.txt', 'r');
}, E_WARNING);

$res = fopen('redis://pokus.txt','w+');
Assert::true($res !== NULL);
$str = 'Hello World';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
Assert::true(fclose($res));
$content = file_get_contents('redis://pokus.txt');
Assert::same($str, $content);

$res = fopen('redis://pokus.txt','w+');
Assert::true($res !== NULL);
$str = 'Hello World';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
Assert::true(fclose($res));
$content = file_get_contents('redis://pokus.txt');
Assert::same($str, $content);


$res = fopen('redis://pokus.txt','r');
Assert::true($res !== NULL);
$ret = fwrite($res,'lorem ipsum');
Assert::same(0, $ret);
Assert::true(fclose($res));
$content = file_get_contents('redis://pokus.txt');
Assert::same($str, $content);

$res = fopen('redis://pokus.txt','a');
Assert::true($res !== NULL);
Assert::same(0, fseek($res,  0, SEEK_SET));
$readed = fread($res, 1000);
Assert::same('', $readed);
Assert::true(fclose($res));

$res = fopen('redis://pokus.txt','a+');
Assert::true($res !== NULL);
Assert::same(0, fseek($res,  -10, SEEK_END));
$readed = fread($res, 5);
Assert::same('ello ', $readed);
$ret = fwrite($res, 'END');
Assert::same(3, $ret);
Assert::true(fclose($res));
$content = file_get_contents('redis://pokus.txt');
Assert::same($str.'END', $content);

$fileToRemove = 'redis://fileToRemove.txt';
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
	unlink('redis://');
}, E_USER_WARNING);

Assert::error(function() {
	$res = fopen('redis://pokus.txt','x');
}, E_WARNING);

$fileToCreate = 'redis://fileToCreate.txt';
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
	$res = fopen('redis://pokus.txt','@');
}, E_WARNING);

$fileToTruncate = 'redis://fileToTruncate.txt';
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
$res = opendir('redis://');
Assert::true($res !== NULL);
closedir($res);

$res = opendir('redis://');
Assert::true($res !== NULL);
$files = [];
while (false !== ($file = readdir($res))) {
	$files[] = $file;
}
Assert::contains('redis://', $files);
Assert::contains($fileToTruncate, $files);
rewinddir($res);
Assert::same('redis://', readdir($res));

closedir($res);

Assert::false(mkdir($fileToTruncate));
Assert::false(mkdir('redis://a/b/c/'));
Assert::false(file_exists('redis://a'));
Assert::true(mkdir('redis://a'));
Assert::true(mkdir('redis://a/b/c',0700, true));
Assert::false(rmdir('redis://a'));
Assert::true(rmdir('redis://a/b/c'));
Assert::false(file_exists('redis://a/b/c/'));
$context = stream_context_create(array('dir' => array('recursive' => true)));
Assert::true(rmdir('redis://a', $context));
Assert::false(file_exists('redis://a'));

$from = 'redis://file1.txt';
$to = 'redis://file2.txt';
@file_put_contents($from,'lorem ipsum');
@unlink($to);
Assert::error(function() use($from) {
	rename($from, 'redis://non/existing/directory/soubor.txt');
}, E_USER_WARNING);

Assert::true(rename($from, $to));
Assert::true(file_exists($to));
Assert::false(file_exists($from));
$dir = 'redis://movehere/';
@unlink($dir);
@mkdir($dir);
Assert::true(rename($to, $dir));
Assert::true(file_exists($dir.'file2.txt'));
