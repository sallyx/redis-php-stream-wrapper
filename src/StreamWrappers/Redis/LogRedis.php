<?php

namespace Sallyx\StreamWrappers\Redis;
use Sallyx\StreamWrappers\Logger;

/**
 * @author petr
 */
class LogRedis extends \Redis implements iRedis {

	/**
	 * Logger
	 */
	private $logger;

	public function __construct(Logger $logger) {
		parent::__construct();
		$this->logger = $logger;
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout
	 * @param mixed $reserved
	 * @param int $retry_interval
	 * @return bool
	 */
	public function connect($host, $port, $timeout, $reserved, $retry_interval)
	{
		return $this->logMethod('connect', func_get_args());
	}

	/**
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->logMethod('isConnected', func_get_args());
	}

	/**
	 * @return bool
	 */
	public function close()
	{
		return $this->logMethod('close', func_get_args());
	}

	/**
	 * @param string $pattern
	 * @return array
	 */
	public function keys($pattern)
	{
		return $this->logMethod('keys', func_get_args());
	}


	/**
	 * @param string $key
	 * @param string $member
	 * @return string|FALSE
	 */
	public function hGet($key, $member)
	{
		return $this->logMethod('hGet', func_get_args());
	}

	/**
	 * @param string $key
	 * @param array $members
	 * @return array
	 */
	public function hMGet($key, $member)
	{
		return $this->logMethod('hMGet', func_get_args());
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function hGetAll($key)
	{
		return $this->logMethod('hGetAll', func_get_args());
	}


	/**
	 * @param string $key
	 * @param string $member
	 * @param string $value
	 * @return bool
	 */
	public function hSetNx($key, $member, $value)
	{
		return $this->logMethod('hSetNx', func_get_args());
	}

	/**
	 * @param string $key
	 * @param string $member
	 * @param string $value
	 * @return int|FALSE
	 */
	public function hSet($key, $member, $value)
	{
		return $this->logMethod('hSet', func_get_args());
	}

	/**
	 * @param string $key
	 * @param array $members
	 * @return bool
	 */
	public function hMSet($key, $members)
	{
		return $this->logMethod('hMSet', func_get_args());
	}

	/**
	 * @param string $key
	 * @param array $member
	 * @param int $value
	 * @return int
	 */
	public function hIncrBy($key, $member, $value)
	{
		return $this->logMethod('hIncrBy', func_get_args());
	}

	/**
	 * @param int &$it
	 * @param string $pattern
	 * @param int $count
	 * @return array|FALSE
	 */
	public function scan(&$it, $pattern, $count)
	{
		$start = microtime(true);
		$result = parent::scan($it, $pattern, $count);
		$this->logger->log('scan', microtime(true)-$start, func_get_args(), $result);
		return $result;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param mixed $options
	 */
	public function set($key, $value, $options)
	{
		return $this->logMethod('set', func_get_args());
	}

	/**
	 * @param string $key
	 */
	public function del($key) {
		return $this->logMethod('del', func_get_args());
	}

	/**
	 * @param string $script
	 * @param array $args
	 * @param int $num_keys
	 * @return mixed
	 */
	public function evaluate($script, $args, $num_keys)
	{
		return $this->logMethod('evaluate', func_get_args());
	}


	/**
	 * @return string|NULL
	 **/
	public function getLastError()
	{
		return $this->logMethod('getLastError', func_get_args());
	}

	/**
	 * @return bool
	 */
	public function clearLastError()
	{
		return $this->logMethod('clearLastError', func_get_args());
	}


	private function logMethod($method, $args)
	{
		$start = microtime(true);
		$result = call_user_func_array('parent::'.$method, $args);
		$this->logger->log($method, microtime(true)-$start, $args, $result);
		return $result;
	}
}
