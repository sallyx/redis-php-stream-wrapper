<?php

namespace Sallyx\Bridges\StreamWrappers\Nette\DI;

use Nette;
use Nette\DI\Compiler;
use Nette\DI\Config;

/**
 * Description of StreamWrappersExtension
 *
 * @author petr
 */
class StreamWrappersExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	private $defaults = array(
		'debugger' => '%debugMode%',
		'route' => 'sallyx-streamwrappers-redis'
	);

	/**
	 * @var array
	 */
	private $configuration;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());
		$config = $builder->expand($config);
		$this->configuration = $config;
		if (!$config['debugger']) {
			return;
		}

		$builder->addDefinition($this->prefix('redisPresenter'))
			->setClass('SallyxBridgesStreamWrappersNettePresentersModule\\RedisPresenter')
			->setInject(TRUE)->setAutowired(TRUE);
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if (!$this->configuration['debugger']) {
			return;
		}
		$route = $this->configuration['route'];
		$initialize = $class->methods['initialize'];
		$initialize->addBody(
			"\$router = \$this->getByType('\\Nette\\Application\\IRouter');\n" .
			"\$router[] = new Nette\\Application\\Routers\\Route('" . $route . "', ['module' => 'SallyxBridgesStreamWrappersNettePresenters', 'presenter' => 'Redis']);\n" .
			"\\Sallyx\\Bridges\\StreamWrappers\\Nette\\Diagnostic\\Panel::register(\$this);\n"
		);
	}

}
