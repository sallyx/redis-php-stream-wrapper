<?php

namespace Sallyx\StreamWrappers;

/**
 * @author petr
 */
interface Logger {

	/**
	 * @param string $method
	 * @param int $ms miliseconds
	 * @param array $args Method arguments
	 * @param mixed $result
	 * @return void
	 */
	public function log($method, $ms, $args, $result);
}
