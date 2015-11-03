<?php

namespace Sallyx\StreamWrappers\Redis;

use Sallyx\StreamWrappers\FSLock;
use Sallyx\StreamWrappers\Redis\PathTranslator;
use RedLock\RedLock;

/**
 * @author petr
 */
class Lock implements FSLock
{

	/**
	 * @var RedLock
	 */
	private $redLock;

	/**
	 * @var PathTranslator
	 */
	private $translator;

	public function __construct(RedLock $redLock, PathTranslator $translator)
	{
		$this->redLock = $redLock;
		$this->translator = $translator;
	}

	/**
	 * @param string $filename
	 * @param int $timeout
	 * @return mixed
	 */
	public function lock($filename, $timeout)
	{
		$key = $this->translator->toLockFile($filename);
		$lock = $this->redLock->lock($key, $timeout);
		if ($lock === FALSE) {
			return NULL;
		}
		return $lock;
	}

	/**
	 * @param mixed $lock
	 * @return bool
	 * @throws \Exception
	 */
	public function unlock($lock)
	{
		return $this->redLock->unlock($lock);
	}

}
