<?php

namespace Sallyx\Bridges\StreamWrappers\Nette\Diagnostic;

use Nette;
use Tracy\Debugger;
use Tracy\IBarPanel;
use Sallyx\StreamWrappers\Wrapper;
use SallyxBridgesStreamWrappersNettePresentersModule\RedisPresenter;

class Panel extends Nette\Object implements IBarPanel
{

	/**
	 * @var string
	 */
	private $scheme;

	/**
	 *
	 * @var \Nette\DI\Container
	 */
	private $container;

	public function __construct($scheme, \Nette\DI\Container $container)
	{
		$this->scheme = $scheme;
		$this->container = $container;
	}

	public function getPanel()
	{
		$app = $this->container->getByType('Nette\Application\Application');
		$link =  $app->getPresenter()->link(':SallyxBridgesStreamWrappersNettePresenters:Redis:');
		$jsScript =  file_get_contents(__DIR__ . '/assets/main.js');
		$jsScript = str_replace(
			array(
				'%redisPresenterLink%',
				'%scheme%'
			),
			array(
				$link,
				$this->scheme,
			),
			$jsScript
		);
		return '<h1>Redis stream wrapper ' . $this->scheme . '://</h1>' .
			'<div class="nette-inner tracy-inner">' .
			'<style type="text/css">' . file_get_contents(__DIR__ . '/assets/style.css') . '</style>' .
			'<div class="sallyx-streamWrappers-fileBrowser '.$this->scheme.'"></div>'.
			'<script type="text/javascript">' . $jsScript . '</script>' .
			'</div>';
	}

	public function getTab()
	{
		return '<span>' . file_get_contents(__DIR__ . '/assets/logo.svg') . '<span class="tracy-label">' . $this->scheme . '://</span></span>';
	}

	/**
	 * @return void
	 */
	public static function register(\Nette\DI\Container $container)
	{
		//self::getDebuggerBlueScreen()->addPanel(array(new static(), 'renderException'));
		foreach (Wrapper::getRegisteredWrappers() as $scheme => $fileSystem) {
			$panel = new static($scheme, $container);
			self::getDebuggerBar()->addPanel($panel);
		}
	}

	/**
	 * @return \Tracy\Bar
	 */
	private static function getDebuggerBar()
	{
		return Debugger::getBar();
	}

	/**
	 * @return \Tracy\BlueScreen
	 */
	private static function getDebuggerBlueScreen()
	{
		return Debugger::getBlueScreen();
	}

}
