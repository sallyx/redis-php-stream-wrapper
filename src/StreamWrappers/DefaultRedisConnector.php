<?php
namespace Sallyx\StreamWrappers;
use Redis;

class DefaultRedisConnector implements RedisConnector {

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
	 * @var Redis
	 */
	private $redis;

	/**
	 * @var bool If True, disconnect() do nothing
	 */
	private $keep_connected = TRUE;

	public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 0, $persistent_id = NULL, $retry_interval = NULL) {
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		$this->persistent_id = $persistent_id;
		$this->retry_interval = $retry_interval;
	}

	public function anableDisconnection() {
		$this->keep_connection = FALSE;
	}

	public function connect() {
		if($this->redis !== NULL) {
			return $this->redis;
		}
		$this->redis = new Redis();
		$this->redis->connect($this->host, $this->port, $this->timeout, $this->persistent_id, $this->retry_interval);
		return $this->redis;
	}

	public function disconnect() {
		if(!$this->keep_connected) {
			$this->redis->close();
			$this->redis = NULL;
		}
	}
}
