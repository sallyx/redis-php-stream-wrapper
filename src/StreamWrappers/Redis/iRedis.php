<?php

namespace Sallyx\StreamWrappers\Redis;

/**
 * @author petr
 */
interface iRedis
{

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout
	 * @param mixed $reserved
	 * @param int $retry_interval
	 * @return bool
	 */
	public function connect($host, $port, $timeout, $reserved, $retry_interval);

	/**
	 * @return bool
	 */
	public function isConnected();

	/**
	 * @return bool
	 */
	public function close();

	/**
	 * @param string $pattern
	 * @return array
	 */
	public function keys($pattern);

	/**
	 * @param string $key
	 * @param string $member
	 * @return string|FALSE
	 */
	public function hGet($key, $member);

	/**
	 * @param string $key
	 * @param array $members
	 * @return array
	 */
	public function hMGet($key, $member);

	/**
	 * @param string $key
	 * @return array
	 */
	public function hGetAll($key);

	/**
	 * @param string $key
	 * @param string $member
	 * @param string $value
	 * @return bool
	 */
	public function hSetNx($key, $member, $value);

	/**
	 * @param string $key
	 * @param string $member
	 * @param string $value
	 * @return int|FALSE
	 */
	public function hSet($key, $member, $value);

	/**
	 * @param string $key
	 * @param array $members
	 * @return bool
	 */
	public function hMSet($key, $members);

	/**
	 * @param string $key
	 * @param array $member
	 * @param int $value
	 * @return int
	 */
	public function hIncrBy($key, $member, $value);

	/**
	 * @param int &$it
	 * @param string $pattern
	 * @param int $count
	 * @return array|FALSE
	 */
	public function scan(&$it, $pattern, $count);

	/**
	 * @param string $key
	 * @param string $value
	 * @param mixed $options
	 */
	public function set($key, $value, $options);

	/**
	 * @param string $key
	 */
	public function del($key);

	/**
	 * @param string $script
	 * @param array $args
	 * @param int $num_keys
	 * @return mixed
	 */
	public function evaluate($script, $args, $num_keys);

	/**
	 * @return string|NULL
	 * */
	public function getLastError();

	/**
	 * @return bool
	 */
	public function clearLastError();
}
