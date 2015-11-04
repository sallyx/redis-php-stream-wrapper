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
	 * @var ConnectorConfig
	 */
	private $config;

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
	 * @param ConnectorConfig $config
	 * @param PathTranslator $translator
	 * @param Logger $logger
	 */
	public function __construct(ConnectorConfig $config, PathTranslator $translator, Logger $logger = null)
	{
		$this->config = $config;
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
		if ($this->logger) {
			$redis = new LogRedis($this->logger);
		} else {
			$redis = new Redis();
		}
		$cc = $this->config;
		$redis->connect($cc->host, $cc->port, $cc->timeout, $cc->persistent_id, $cc->retry_interval);
		if (!$redis->isConnected()) {
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
