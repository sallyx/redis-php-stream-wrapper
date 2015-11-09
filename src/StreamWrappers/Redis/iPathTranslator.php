<?php

namespace Sallyx\StreamWrappers\Redis;

/**
 * @author petr
 */
interface iPathTranslator
{

	/**
	 * @param string $filename
	 * @return string
	 */
	public function toKey($filename);

	/**
	 * @param string $key
	 * @return string
	 */
	public function toFile($key);

	/**
	 * @param string $filename
	 * @return string
	 */
	public function toLockFile($filename);
}
