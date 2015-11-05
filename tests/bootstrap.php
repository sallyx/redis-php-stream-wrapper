<?php

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

use Sallyx\StreamWrappers\Redis\Connector;
use Sallyx\StreamWrappers\Redis\ConnectorConfig;
use Sallyx\StreamWrappers\Redis\PathTranslator;
use Sallyx\StreamWrappers\Redis\FileSystem;
use Sallyx\StreamWrappers\Wrapper;
use Tester\Assert;

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

function _register_wrapper($namespace)
{
	$fs = new FileSystem(new Connector(new ConnectorConfig, new PathTranslator($namespace)));
	Assert::true(Wrapper::register($fs));

	$context = stream_context_create(array('dir' => array('recursive' => true)));
	@rmdir('redis://', $context);
}
