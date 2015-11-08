<?php

require_once '../bootstrap.php';

use Tester\Assert;

_register_wrapper('fs::');

Assert::error(function() {
	$res = fopen('redis://not-exists.txt', 'r');
}, E_WARNING);

$filename = 'redis://pokus.txt';
$str = 'Hello World ';

file_put_contents($filename, $str);
$res = fopen($filename, 'r');
Assert::true($res !== NULL);
$ret = fwrite($res, 'lorem ipsum');
Assert::same(0, $ret);
Assert::true(fclose($res));
$content = file_get_contents($filename);
Assert::same($str, $content);

$res = fopen($filename, 'a');
Assert::true($res !== NULL);
Assert::same(0, fseek($res, 0, SEEK_SET));
$readed = fread($res, 1000);
Assert::same('', $readed);
Assert::true(fclose($res));

file_put_contents($filename, 'Hello World');
$res = fopen($filename, 'a+');
Assert::true($res !== NULL);
Assert::same(0, fseek($res, -10, SEEK_END));
Assert::false(feof($res));
$readed = fread($res, 5);
Assert::same('ello ', $readed);
$ret = fwrite($res, 'END');
Assert::true(feof($res));
Assert::same(3, $ret);
Assert::true(fclose($res));
$content = file_get_contents($filename);
Assert::same('Hello WorldEND', $content);

$fileToRemove = 'redis://fileToRemove.txt';
@unlink($fileToRemove); // $fileToRemove might or might not exists
Assert::false(file_exists($fileToRemove));
$res = fopen($fileToRemove, 'w');
Assert::true($res !== NULL);
Assert::true(fclose($res));
clearstatcache();
Assert::true(file_exists($fileToRemove));
unlink($fileToRemove);
clearstatcache();
Assert::false(file_exists($fileToRemove));
Assert::error(function() {
	unlink('redis://');
}, E_USER_WARNING);

Assert::error(function() use($filename) {
	$res = fopen($filename, 'x');
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

Assert::error(function() use($filename) {
	$res = fopen($filename, '@');
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



$from = 'redis://file1.txt';
$to = 'redis://file2.txt';
@file_put_contents($from, 'lorem ipsum');
@unlink($to);
Assert::error(function() use($from) {
	rename($from, 'redis://non/existing/directory/soubor.txt');
}, E_USER_WARNING);

Assert::true(rename($from, $to));
Assert::true(file_exists($to));
Assert::false(file_exists($from));
$dir = 'redis://movehere/';
$from = $to;
@unlink($dir);
@mkdir($dir);
Assert::true(rename($from, $dir));
Assert::true(file_exists($dir . 'file2.txt'));
