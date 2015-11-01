<?php
// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
        echo 'Install Nette Tester using `composer install`';
        exit(1);
}


Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');


// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);
ini_set('session.save_path', TEMP_DIR);

require_once __DIR__ . '/../src/StreamWrappers/RedisWrapper.php';
