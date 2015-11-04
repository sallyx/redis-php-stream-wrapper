<?php

namespace Sallyx\StreamWrappers\Redis;

use Sallyx\StreamWrappers\Logger;

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
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var bool If True, disconnect() do nothing
	 */
	private $keep_connected = TRUE;

	/**
	 * @param ConnectorConfig $cc
	 * @param PathTranslator $translator
	 * @param Logger $logger
	 */
	public function __construct(ConnectorConfig $cc, PathTranslator $translator, Logger $logger = null)
	{
		$this->host = $cc->host;
		$this->port = $cc->port;
		$this->timeout = $cc->timeout;
		$this->persistent_id = $cc->persistent_id;
		$this->retry_interval = $cc->retry_interval;
		$this->translator = $translator;
		$this->logger = $logger;
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
		if ($this->keep_connected && $this->storage !== NULL) {
			return $this->storage;
		}
		if($this->logger) {
			$redis = new LogRedis($this->logger);
		} else {
			$redis = new Redis();
		}
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
