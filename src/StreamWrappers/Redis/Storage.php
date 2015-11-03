<?php

namespace Sallyx\StreamWrappers\Redis;

use RedLock\RedLock;
use Sallyx\StreamWrappers\FSLock;

/**
 * Description of Storage
 *
 * @author petr
 */
class Storage
{

	/**
	 * @var \Redis
	 */
	private $redis;
	private $translate;

	public function __construct(\Redis $redis, PathTranslator $translator)
	{
		$this->redis = $redis;
		$this->translate = $translator;
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	private function toKey($filename) {
		return $this->translate->toKey($filename);
	}

	/**
	 * @return bool
	 */
	public function close()
	{
		return $this->redis->close();
	}

	/**
	 * @param string $dirname
	 * @return array
	 */
	public function getDirectoryFiles($dirname)
	{
		return array_map(
			array($this->translate,'toFile'),
			$this->redis->keys($this->toKey($dirname) . '*')
		);
	}

	/**
	 * @param string $filename
	 * @param string $property
	 * @return string|FALSE
	 */
	public function getFileProperty($filename, $property)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hGet($filename, $property);
	}

	/**
	 * @param string $filename
	 * @param array $properties
	 * @return array
	 */
	public function getFileProperties($filename, array $properties)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hMGet($filename, $properties);
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	public function getFile($filename)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hGetAll($filename);
	}

	/**
	 * @param string $filename
	 * @param string $propertyName
	 * @param string $value
	 * @return bool
	 */
	public function createFileWithProperty($filename, $propertyName, $value)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hSetNx($filename, $propertyName, $value);
	}

	/**
	 *
	 * @param string $filename
	 * @param string $propertyName
	 * @param string $value
	 * @return int|FALSE
	 */
	public function setFileProperty($filename, $propertyName, $value)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hSet($filename, $propertyName, $value);
	}

	/**
	 * @param string $filename
	 * @param array $properties
	 * @return bool
	 */
	public function setFileProperties($filename, array $properties)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hMSet($filename, $properties);
	}

	/**
	 * @param string $filename
	 * @param string $propertyName
	 * @param int $value
	 * @return int
	 */
	public function incrFilePropertyBy($filename, $propertyName, $value)
	{
		$filename = $this->toKey($filename);
		return $this->redis->hIncrBy($filename, $propertyName, $value);
	}

	/**
	 *
	 * @param string $dirname
	 * @param int $count
	 * @param int &$it
	 * @return array|FALSE
	 */
	public function scanDirectory($dirname, $count, &$it)
	{
		$dirname = $this->toKey($dirname);
		return $this->redis->scan($it, $dirname . '*', $count);
	}

	/**
	 * @param type $filename
	 * @return int 1 if file was deleted, otherwise 0
	 */
	public function del($filename)
	{
		$filename = $this->toKey($filename);
		return $this->redis->del($filename);
	}

	/**
	 *
	 * @param string $script LUA script
	 * @param array $params
	 * @param int $keysCount
	 * @return mixed
	 */
	public function evaluate($script, array $params = NULL, $keysCount)
	{
		$i = 0;
		$params = array_values($params);
		for($i = 0; $i < $keysCount; $i++) {
			$params[$i] = $this->toKey($params[$i]);
		}

		$ret = $this->redis->evaluate($script, $params, $keysCount);
		$err = $this->redis->getLastError();
		if ($err) {
			$this->redis->clearLastError();
			trigger_error($err, E_USER_WARNING);
		}
		return $ret;
	}

	/**
	 *
	 * @param boolean $nb
	 * @return FSLock
	 */
	public function getLock($nb)
	{
		$redLock = new RedLock([$this->redis], 200, $nb ? 1 : 50);
		return new Lock($redLock, $this->translate);
	}

}
