<?php

namespace Sallyx\StreamWrappers\Redis;

class ConnectorConfig
{

	/**
	 * @var string Can be a host, or the path to a unix domain socket
	 */
	private $host;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * @var float value in seconds (optional, default is 0 meaning unlimited)
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

	public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 0, $persistent_id = NULL, $retry_interval = NULL)
	{
		$this->host = $host;
		$this->port = $this->host[0] == '/' ? NULL : $port;
		$this->timeout = $timeout;
		$this->persistent_id = $persistent_id;
		$this->retry_interval = $retry_interval;
	}

	public function __get($name)
	{
		if (isset($this->$name)) {
			return $this->$name;
		}
	}

}
