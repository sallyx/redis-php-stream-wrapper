<?php

require_once '../bootstrap.php';

use Tester\Assert;

_register_wrapper('lock::');

$lockFile = 'redis://lockfile.txt';
$lockFile2 = 'redis://lockfile.txt';
Assert::same(11, file_put_contents($lockFile, 'lorem ipsum'));
Assert::same(11, file_put_contents($lockFile2, 'lorem ipsum'));

$fs1 = fopen($lockFile, 'r+');
Assert::true($fs1 !== NULL);
$fs2 = fopen($lockFile, 'r+');
Assert::true($fs2 !== NULL);
$fs3 = fopen($lockFile, 'r+');
Assert::true($fs3 !== NULL);

Assert::false(flock($fs1, LOCK_UN | LOCK_NB));
Assert::true(flock($fs2, LOCK_SH));
Assert::false(flock($fs3, LOCK_EX | LOCK_NB));
Assert::true(flock($fs2, LOCK_UN));
Assert::true(flock($fs1, LOCK_EX));
Assert::true(flock($fs1, LOCK_UN));
Assert::true(flock($fs3, LOCK_EX));
Assert::true(flock($fs3, LOCK_SH));
Assert::true(flock($fs3, LOCK_UN));
Assert::true(fclose($fs1));
Assert::true(fclose($fs2));

$fs = fopen($lockFile2, 'r+');
Assert::true($fs2 !== NULL);
Assert::true(fclose($fs3));

Assert::true(flock($fs, LOCK_SH));
Assert::true(flock($fs, LOCK_UN));
Assert::true(fclose($fs));

$fs1 = fopen($lockFile, 'r+');
Assert::true($fs1 !== NULL);
Assert::true(flock($fs1, LOCK_EX | LOCK_NB));
$fs1 = NULL;
$fs1 = fopen($lockFile, 'r+');
Assert::true($fs1 !== NULL);
Assert::true(flock($fs1, LOCK_EX | LOCK_NB));
Assert::true(flock($fs1, LOCK_UN));
Assert::true(fclose($fs1));
