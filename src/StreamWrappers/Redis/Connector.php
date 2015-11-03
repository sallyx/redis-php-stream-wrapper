<?php

namespace Sallyx\StreamWrappers\Redis;

use Redis;

class Connector
{
	/**
	 * @var PathTranslator
	 */
	private $translator;

	/**
	 * @var string Can be a host, or the path to a unix domain socket
	 */
	private $host;

	/**
	 * @var int port
	 */
	private $port;

	/**
	 * @var float  value in seconds (optional, default is 0 meaning unlimited)
	 */
	private $timeout;

	/**
	 * @var string identity for the requested persistent connection
	 */
	private $persistent_id;

	/**
	 * @var int value in milliseconds (optional)
	 */
	private $retry_interval;

	/**
	 * @var Storage
	 */
	private $storage;

	/**
	 * @var bool If True, disconnect() do nothing
	 */
	private $keep_connected = TRUE;

	/**
	 * @param PathTranslator $translator
	 * @param string $host
	 * @param int|NULL $port
	 * @param int $timeout
	 * @param string $persistent_id
	 * @param int $retry_interval
	 */
	public function __construct(PathTranslator $translator, $host = '127.0.0.1', $port = 6379, $timeout = 0, $persistent_id = NULL, $retry_interval = NULL)
	{
		$this->translator = $translator;
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		$this->persistent_id = $persistent_id;
		$this->retry_interval = $retry_interval;
	}

	/**
	 * @return void
	 */
	public function anableDisconnection()
	{
		$this->keep_connection = FALSE;
	}

	/**
	 * @return Storage|NULL
	 */
	public function connect()
	{
		if ($this->storage !== NULL) {
			return $this->storage;
		}
		$redis = new Redis();
		$redis->connect($this->host, $this->port, $this->timeout, $this->persistent_id, $this->retry_interval);
		if(!$redis->isConnected()) {
			return NULL;
		}
		$this->storage = new Storage($redis, $this->translator);
		return $this->storage;
	}

	/**
	 * @return bool
	 */
	public function disconnect()
	{
		if ($this->storage === NULL) {
			return FALSE;
		}
		if (!$this->keep_connected) {
			$this->storage->close();
			$this->storage = NULL;
		}
		return TRUE;
	}

}
