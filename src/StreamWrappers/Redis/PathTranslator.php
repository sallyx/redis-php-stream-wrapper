<?php

namespace Sallyx\StreamWrappers\Redis;

/**
 * Translate file names to redis keys and vice versa
 *
 * @author petr
 */
class PathTranslator
{
	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var int
	 */
	private $prefixLen;

	/**
	 * @param string $prefix
	 */
	public function __construct($prefix)
	{
		$this->prefix = $prefix;
		$this->prefixLen = strlen($prefix);
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	public function toKey($filename)
	{
		return $this->prefix . '/' . $filename;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function toFile($key)
	{
		return \substr($key, $this->prefixLen + 1);
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	public function toLockFile($filename)
	{
		return $this->prefix . '~' . $filename;
	}

}
