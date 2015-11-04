<?php
namespace Sallyx\Bridges\StreamWrappers\Nette\Tracy;

use Sallyx\StreamWrappers\Logger;

class RedisLogger implements Logger {

	/**
	 * @param string $method
	 * @param int $ms miliseconds
	 * @param array $args Method arguments
	 * @param mixed $result
	 * @return void
	 */
	public function log($method, $ms, $args, $result) {
	// printf("%5.3f - %s = %s<br />\n", $ms, $method, serialize($result));
	}
}
