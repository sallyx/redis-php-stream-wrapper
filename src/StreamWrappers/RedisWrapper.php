<?php
namespace Sallyx\StreamWrappers;

use Redis, RedisException;


class RedisWrapper {

	/**
	 * @var resource
	 */
	public $context;

	/**
	 * @var  array[string]RedisConnector
	 */
	private static $connectors = [];

	/**
	 * @var RedisConnector 
	 */
	private $connector;

	/**
	 * @var Redis
	 */
	private $redis;

	/**
	 * @var string
	 */
	private $scheme;

	/**
	 * @var string
	 */
	private $namespace;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $mode;

	/**
	 * @var int
	 */
	private $fpos = 0;

	private $dirEntries = NULL;

	private $dirIndex = 0;


	/**
	 * Register a redis wrapper
	 * @param $wrapperName Name of the stream (i.e. redis for redis://...)
	 * @return bool Returns TRUE on success or FALSE on failure. 
	 */
	public static function register($wrapperName='redis', RedisConnector $connector) {
		if(isset(self::$connectors[$wrapperName])) {
			throw new \InvalidStateException('Connector already registered.');
		}
		self::$connectors[$wrapperName] = $connector;
		return \stream_wrapper_register($wrapperName, self::class, 1);
	}

	/**
	 * Constructor
	 */
	public function __construct() {
        }

	/**
	 * Destructor
	 */
	public function __destruct() {
	}

	/**
	 *  Close directory handle
	 */
	public function dir_closedir() {
		$this->connector->disconnect();
		$this->connector = NULL;
		$this->redis = NULL;
		$this->dirEntries = NULL;
		return TRUE;
	}

	/**
	 * Open directory handle
	 */
	public function dir_opendir($path, $options) {
		if(!$this->initPath($path)) {
			trigger_error("$path isn't readable for me", E_USER_NOTICE);
			return FALSE;
		}
		$directory = $this->readDirectory($this->getFileName());
		if(empty($directory)) {
			trigger_error("$path isn't directory", E_USER_NOTICE);
			return FALSE;
		}
		return TRUE;
	}

	public function dir_readdir() {
		if($this->dirEntries === NULL) {
			$this->dirIndex = 0;
			$this->dirEntries = $this->redis->keys($this->getFileName().'*');
			if($this->dirEntries === FALSE) {
				$this->dirEntries = NULL;
				trigger_error("reading $path error", E_USER_NOTICE);
				return FALSE;
			}
			$len = strlen($this->getRootDirectory());
			$prefix = $this->scheme.'://'.$this->namespace;
			$this->dirEntries = array_map(function($name)  use ($len, $prefix) {
				return $prefix.substr($name, $len);
			}, $this->dirEntries);
			sort($this->dirEntries, SORT_NATURAL);
		}
		if(!isset($this->dirEntries[$this->dirIndex])) {
			return FALSE;
		}
		return $this->dirEntries[$this->dirIndex++];
	}

	/**
	 *  Rewind directory handle
	 */
	public function dir_rewinddir() {
		$this->dirIndex = 0;
	}

	public function mkdir($path, $mode, $options) {
		if(!$this->initPath($path)) {
			return FALSE;
		}
		$recursive = $options & STREAM_MKDIR_RECURSIVE;
		$dirs = explode('/', $this->path);
		$next = '';
		$dirname = '';
		$created = FALSE;
		do {
			if(!$next && $dirname) continue;
			$dirname .= ($dirname === '/') ? $next : '/'.$next;
			$key = $this->getKeyByName($dirname);
			if($recursive || $dirname === '/') {
				$created = $this->createDirectory($key);
			}
			$type = $this->redis->hGet($key, 'type');
			if($type !== 'd') {
				if($type !== FALSE) return FALSE;
				if(!empty($dirs)) return FALSE;
				$created = FALSE;
				break;
			}
		} while (($next = array_shift($dirs)) !== NULL);
		if(!$created) {
			$created = $this->createDirectory($key);
		}
		return !!$created;
	}


	/**
	 * Removes a directory
	 */
	public function rmdir($path, $options) {
		if(!$this->initPath($path)) {
			return FALSE;
		}
		$recursive = $options & STREAM_MKDIR_RECURSIVE;
		
		if(!$recursive && $this->context !== NULL) {
			$options = stream_context_get_options($this->context);
			$recursive = !empty($options['dir']['recursive']);
		}

		$type = $this->redis->hGet($this->getFileName(), 'type');
		if($type !== 'd')
			return FALSE;
		if(!$recursive) {
			$c = $this->isDirectoryEmpty($this->getFileName());
			if(!$c) {
				return FALSE;
			}
		}
		$ret = $this->deleteRecursive($this->getFileName());
		return $ret;
	}

	/**
	 * Close a resource
	 * @return void
	 */
	public function stream_close() {
		$this->dir_closedir();
	}

	/**
	 * This method is called in response to feof(). 
	 */
	public function stream_eof() {
		$len = $this->getLength();
		return ($this->fpos < $len);
	}

	/**
	 * Flushes the output
	 */
	public function stream_flush() {
	}


	/**
	 * This method is called immediately after the wrapper is initialized (f.e. by fopen() and file_get_contents()). 
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {
		if(!$this->initPath($path)) {
			return FALSE;
		}
		$this->mode = preg_replace('/[tb]/','', $mode); // ignore text/binary mode
		$mode = $this->mode;

		if($mode === 'r' || $mode === 'r+') {
			$ctime = $this->redis->hGet($this->getFileName(), 'ctime');
			if($ctime === FALSE) {
				return FALSE;
			}
		} else if ($mode === 'w' || $mode === 'w+') {
			$ctime = $this->redis->hGet($this->getFileName(), 'ctime');
			if($ctime === FALSE) {
				if(!$this->createFile()) {
					return FALSE;
				}
			}
			$this->truncateFile();
		} else if ($mode === 'a' || $mode === 'a+') {
			$size = $this->redis->hGet($this->getFileName(), 'size');
			if($size === FALSE) {
				if(!$this->createFile())
					return FALSE;
				$size = 0;
			}
			$this->fpos = $size;
		} else if ($mode === 'x' || $mode === 'x+') {
			$ctime = $this->redis->hGet($this->getFileName(), 'ctime');
			if($ctime) {
				return FALSE;
			}
			if(!$this->createFile())
				return FALSE;
		} else if ($mode === 'c' || $mode === 'c+') {
			$ctime = $this->redis->hGet($this->getFileName(), 'ctmie');
			if($ctime === FALSE) {
				if(!$this->createFile())
					return FALSE;
			}
			$this->fpos = 0;
		} else {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * This method is called in response to fread() and fgets().
	 */
	public function stream_read ($count) {
		if(!in_array($this->mode, ['r','r+','w+','a+','x+','c+'])) {
			return FALSE;
		}
		$file = $this->redis->hMGet(
			$this->getFileName(), 
			array('type', 'content')
		);
		if(empty($file)) {
			return FALSE;
		}
		if($file['type'] !== 'f') {
			return FALSE;
		}

		$ret = substr($file['content'], $this->fpos, $count);
		$this->fpos += strlen($ret);
		return $ret;
	}

	public function stream_seek($offset, $whence) {
		switch($whence) {
			case SEEK_SET:
				$this->fpos = $offset;
				return TRUE;
			case SEEK_CUR:
				$this->fpos += $offset;
				return TRUE;
			case SEEK_END:
				$size = $this->redis->hGet($this->getFileName(), 'size');
				$this->fpos = intval($size) + $offset;
				return TRUE;
		}
		return false;

	}

	/**
	 * Retrieve information about a file resource
	 */
	public function stream_stat() {
		$file = $this->redis->hMGet($this->getFileName(), array('size','atime', 'mtime','ctime'));
		if($file['ctime'] === FALSE) {
			return NULL; 
		}

		$values = array(
			'dev' => 0, //TODO: dbindex
			'ino' => 0,
			'mode' => 0,
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => 0,
			'size'=> $file['size'],
			'atime' => $file['atime'],
			'mtime' => $file['mtime'],
			'ctime' => $file['ctime'],
			'blksize' => -1,
			'blocks' => -1

		);

		return array_merge(array_values($values), $values);
	}


	/**
	 * Write to stream
	 */
	public function stream_write($data) {
		if(!in_array($this->mode, ['w','w+','a','a+','x','x+','c','c+'])) {
			return 0;
		}
		$key = $this->getFileName();
		$dirname = dirname($key);
		$directory = $this->readDirectory($dirname);
		if(empty($directory)) {
			//TODO: trigger_error
			return 0;
		}
		$fpos = $this->fpos;
		if(in_array($this->mode,['a','a+'])) {
			$fpos =  0;
		}
		$value = $this->redis->hGetAll($key);
		if(empty($value)) {
			$value = array(
				'type' => 'f',
				'ctime' => time(),
				'atime' => time(),
				'mtime' => time(),
				'content' => str_repeat("\0", $fpos),
				'size' => $fpos
			);
		}
		if(in_array($this->mode,['a','a+'])) {
			//  In this mode, fseek() only affects the reading position, writes are always appended. 
			$fpos =  $value['size'];
		}
		$value['atime'] = $value['mtime'] = time();
		$value['content'] = substr($value['content'], 0, $fpos). $data;
		$value['size'] = strlen($value['content']);
		if(!$this->redis->hMset($key, $value)) {
			return 0;
		}
		if(!in_array($this->mode,['a','a+'])) {
			$this->fpos = $value['size'];
		}
		return strlen($data);
	}

	/**
	 * Retrieve the current position of a stream
	 */
	public function stream_tell() {
		return $this->fpos;
	}

	public function stream_truncate($new_size) {
		return $this->truncateFile($new_size);
	}

	public function unlink($path) {
		if(!$this->initPath($path)) {
			return FALSE;
		}
		$fileType = $this->redis->hGet($this->getFileName(),'type');
		if($fileType !== 'f') {
			trigger_error("$path is not a file", E_USER_WARNING);
			return FALSE;
		}
		$this->redis->del($this->getFileName()) === 1;
		$file = $this->redis->hMGet($this->getFileName(), array('size','atime', 'mtime','ctime'));
		return true;
	}

	/**
	 * Retrieve information about a file
	 */
	public function url_stat($path, $flags) {
		if($flags & STREAM_URL_STAT_LINK || !$this->initPath($path)) {
			if($flags & STREAM_URL_STAT_QUIET) {
				return array();
			}
			trigger_error('Unsupported operation on file '.$path, E_USER_WARNING);
			return array();
		}
		return $this->stream_stat();
	}

	public function createFile() {
		$key = $this->getFileName();
		$dirname = dirname($key);
		$directory = $this->readDirectory($dirname);
		if(empty($directory)) {
			return FALSE;
		}
		$value = array(
			'type' => 'f',
			'ctime' => time(),
			'atime' => time(),
			'mtime' => time(),
			'content' => str_repeat("\0", $this->fpos),
			'size' => $this->fpos
		);
		return $this->redis->hMset($key, $value);
	}

	public function truncateFile($size=0) {
		$key = $this->getFileName();
		if($size < 0)  {
			$size = 0;
		}
		if($size == 0) {
			$content = '';
		} else {
			$content = $this->redis->hGet($key, 'content');
			if($content === FALSE)
				return FALSE;
		}
		$content = substr($content, 0, $size);
		$content .=  str_repeat("\0", $size-strlen($content));

		$value = array(
			'atime' => time(),
			'mtime' => time(),
			'content' => $content,
			'size' => $size
		);
		//TODO: if exists
		if(!$this->redis->hMset($key, $value)) {
			return FALSE;
		}
		$this->fpos = $size;
		return TRUE;
	}


	public function createDirectory($key) {
		if(!$this->redis->hSetNx($key, 'ctime', time())) {
			return FALSE;
		}
		$dir = array(
			'type' => 'd', 
			'ctime' => time(),
			'atime' => time(),
			'mtime' => time(),
			'content' => $this->serialize(array()), 
			'size' => 0
		);
		if($this->redis->hMset($key, $dir)) {
			return $dir;
		}
	}

	/**
	 * @param string $dirname
	 * @return array
	 */
	public function readDirectory($dirname) {
		$dirname = preg_replace('@/$@', '', $dirname);
		if($dirname === $this->getRootDirectory()) {
			$dirname .= '/';
		}
		$directory = $this->redis->hGetall($dirname);
		if(!empty($directory) && isset($directory['type'])) {
			if($directory['type'] === 'd') {
				return $directory;
			}
			return [];
		}
		if($dirname === $this->getRootDirectory().'/') {
			return $this->createDirectory($dirname);
		}
		return [];
	}

	private function initPath($path) {
		$url = (object) parse_url($path);
		if(empty(self::$connectors[$url->scheme])) {
			return FALSE;
		}
		$this->scheme = $url->scheme;
		$this->connector =  self::$connectors[$url->scheme];
		$this->redis = $this->connector->connect();
		$this->namespace = $url->host;
		$this->path = isset($url->path) ? $this->normalize($url->path) : NULL;

		return TRUE;
	}

	private function normalize($path) {
		$path = preg_replace('@//+@','/', $path);
		$dirs = explode('/', $path);
		$res = [];
		foreach($dirs as $d) {
			if($d === '.') continue;
			if($d === '..') { array_pop($res); continue; }
			$res[] = $d;
		}
		$path = join('/', $res);
		return $path;
	}

	private function getRootDirectory() {
		return $this->namespace.'::';
	}

	private function getKeyByName($name) {
		return $this->getRootdirectory().$name;
	}

	private function getFileName() {
		return $this->getKeyByname($this->path);
	}

	public function getLength() {
		return $this->redis->eval("local content;content = redis.call('hget',KEYS[1],'content'); return string.len(content)", array($this->getFileName()), 1);
	}

	public function isDirectoryEmpty($key) {
		$it = NULL;
		$keys = $this->redis->scan($it, $key.'*', 100);
		if($it === 0 && count($keys) === 1)
			return TRUE;
		return FALSE;
	}

	public function deleteRecursive($key) {
		return 1 === $this->redis->eval("for _,k in ipairs(redis.call('keys',KEYS[1]..'*')) do redis.call('del',k) end; return 1", array($key),1);
	}

	private function serialize($value) {
		return \serialize($value);
	}
}
