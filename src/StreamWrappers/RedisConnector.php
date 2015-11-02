<?php
namespace Sallyx\StreamWrappers;
use Redis;

interface RedisConnector {

	/**
	 * @return Redis Already connected redis intance
	 */
	public function connect();

	/**
	 * Close connection
	 * @return void
	 */
	public function disconnect();
}
