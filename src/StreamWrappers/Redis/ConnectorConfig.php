<?php

namespace Sallyx\StreamWrappers\Redis;

class ConnectorConfig {

	private $host;

	private $port;

	private $timeout;

	private $persistent_id;

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
		if(isset($this->$name)) {
			return $this->$name;
		}
	}
}
