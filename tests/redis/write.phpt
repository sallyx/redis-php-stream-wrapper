<?php

require_once '../bootstrap.php';

_register_wrapper('write::');

use Tester\Assert;

$filename = 'redis://pokus.txt';

$res = fopen($filename, 'wb', true);
Assert::true($res !== NULL);
$str = 'Hello World ';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
$len = fwrite($res, $str, 5);
Assert::same(5, $len);
Assert::true(fclose($res));
$res = fopen($filename, 'r');
Assert::true($res !== NULL);
$readed = fread($res, 12);
Assert::same(12, strlen($readed));
Assert::same('Hello World ', $readed);
$readed = fread($res, 100);
Assert::same(5, strlen($readed));
Assert::same('Hello', $readed);
Assert::true(fclose($res));

file_put_contents($filename, 'Hello World');
$res = fopen($filename, 'c');
Assert::true($res !== NULL);
$strx = 'xxx';
Assert::same(0, fseek($res, 2, SEEK_SET));
$len = fwrite($res, $strx);
Assert::same(strlen($strx), $len);
Assert::true(fclose($res));
$content = file_get_contents($filename);
Assert::same('Hexxx World', $content);

$res = fopen($filename, 'w+');
Assert::true($res !== NULL);
$strx = 'xxx';
Assert::same(0, fseek($res, 30, SEEK_SET));
$len = fwrite($res, $strx);
Assert::same(strlen($strx), $len);
Assert::true(fclose($res));
$content = file_get_contents($filename);
Assert::same(33, strlen($content));

$res = fopen($filename, 'w+');
Assert::true($res !== NULL);
$str = 'Hello World';
$len = fwrite($res, $str);
Assert::same(strlen($str), $len);
Assert::true(fclose($res));
$content = file_get_contents($filename);
Assert::same($str, $content);

$filename = 'redis:///extra-slash.txt';
$res = fopen($filename, 'wb', true);
Assert::true($res !== NULL);
