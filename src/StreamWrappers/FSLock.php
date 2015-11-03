<?php

namespace Sallyx\StreamWrappers;

/**
 * @author petr
 */
interface FSLock
{

	const LOCK_EX = 'ex';
	const LOCK_SH = 'sh';

	/**
	 * @param string $filename
	 * @param int $timeout
	 * @return mixed|NULL Lock object or NULL on failure
	 * @throws \Exception
	 */
	public function lock($filename, $timeout);

	/**
	 * @param mixed $lock
	 * @return bool
	 * @throws \Exception
	 */
	public function unlock($key);
}
