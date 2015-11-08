<?php

require_once '../bootstrap.php';

use Tester\Assert;

_register_wrapper('metadata::');

$testfile = 'redis://test.txt';

Assert::true(touch($testfile));
Assert::true(file_exists($testfile));

$mtime = strtotime('2000-01-01');
$atime = strtotime('2011-11-11');
touch($testfile, $mtime);
clearstatcache();
$stat = stat($testfile);
Assert::same($mtime, $stat['mtime']);
Assert::same($mtime, $stat['atime']);

touch($testfile, $mtime, $atime);
clearstatcache();
$stat = stat($testfile);
Assert::same($mtime, $stat['mtime']);
Assert::same($atime, $stat['atime']);

//not supported (yet) but do not throw errors
Assert::false(chmod($testfile, 0666));
Assert::false(chown($testfile, 'root'));
Assert::false(chgrp($testfile, 'root'));
