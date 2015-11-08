<?php

namespace Sallyx\StreamWrappers;

class Wrapper
{

	/**
	 * @var resource
	 */
	public $context;

	/**
	 * @var  array[string]FileSystem
	 */
	private static $fileSystems = [];

	/**
	 * @var FileSystem
	 */
	private $fileSystem;

	/**
	 * @var string
	 */
	private $scheme;

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

	/**
	 *
	 * @var \Iterator
	 */
	private $dirEntries = NULL;

	/**
	 * Register a redis wrapper
	 * @param FileSystem $fileSystem
	 * @param $wrapperName Name of the stream (i.e. redis for redis://...)
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public static function register(FileSystem $fileSystem, $wrapperName = 'redis')
	{
		if (!preg_match('/[a-z]+/', $wrapperName)) {
			trigger_error('Invalid wrapper name', E_USER_WARNING);
			return FALSE;
		}
		if (isset(self::$fileSystems[$wrapperName])) {
			throw new \InvalidStateException('Connector already registered.');
		}
		self::$fileSystems[$wrapperName] = $fileSystem;
		return \stream_wrapper_register($wrapperName, 'Sallyx\StreamWrappers\Wrapper', 0);
	}

	/**
	 * @return array[string]FileSystem
	 */
	public static function getRegisteredWrappers()
	{
		return self::$fileSystems;
	}

	/**
	 * @param string $wrapperName
	 * @return FileSystem|NULL
	 */
	public static function getRegisteredWrapper($wrapperName)
	{
		if (empty(self::$fileSystems[$wrapperName])) {
			return NULL;
		}
		return self::$fileSystems[$wrapperName];
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		if ($this->lockType !== NULL) {
			$this->stream_lock(LOCK_UN);
		}
		if ($this->fileSystem !== NULL) {
			$this->fileSystem->disconnect();
			$this->fileSystem = NULL;
		}
	}

	/**
	 *  Close directory handle
	 */
	public function dir_closedir()
	{
		$this->dirEntries = NULL;
		return TRUE;
	}

	/**
	 * Open directory handle
	 */
	public function dir_opendir($path, $options)
	{
		if (!$this->initPath($path)) {
			trigger_error("$path isn't readable for me", E_USER_NOTICE);
			return FALSE;
		}
		$directory = $this->fileSystem->isDirectory($this->getFileName());
		if (!$directory) {
			trigger_error("$path isn't directory", E_USER_NOTICE);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Read entry from directory handle
	 */
	public function dir_readdir()
	{
		if ($this->dirEntries === NULL) {
			$this->dirEntries = $this->fileSystem->getDirectoryIterator($this->getFileName());
			if ($this->dirEntries === NULL) {
				trigger_error("Reading '$path' error", E_USER_NOTICE);
				return FALSE;
			}
		}
		if (!$this->dirEntries->valid()) {
			return FALSE;
		}
		$filename = $this->dirEntries->current();
		$this->dirEntries->next();
		return $filename;
	}

	/**
	 *  Rewind directory handle
	 */
	public function dir_rewinddir()
	{
		$this->dirEntries->rewind();
	}

	/**
	 * Create a directory
	 */
	public function mkdir($path, $mode, $options)
	{
		if (!$this->initPath($path)) {
			return FALSE;
		}
		$recursive = $options & STREAM_MKDIR_RECURSIVE;

		$dirs = explode('/', $this->getFileName());
		$next = '';
		$dirname = '';
		$created = FALSE;
		do {
			if (!$next && $dirname)
				continue;
			$dirname .= ($dirname === '/') ? $next : '/' . $next;
			if ($recursive || $dirname === '/') {
				$created = $this->fileSystem->createDirectory($dirname);
			}
			$type = $this->fileSystem->getFileType($dirname);
			if ($type !== FileSystem::FILE_TYPE_DIRECTORY) {
				if ($type !== FALSE) {
					return FALSE;
				}
				if (!empty($dirs)) {
					return FALSE;
				}
				$created = FALSE;
				break;
			}
		} while (($next = array_shift($dirs)) !== NULL);
		if (!$created) {
			$created = $this->fileSystem->createDirectory($dirname);
		}
		return !!$created;
	}

	/**
	 * Renames a file or directory
	 */
	public function rename($path_from, $path_to)
	{
		if (!$this->initPath($path_to)) {
			return FALSE;
		}
		$keyTo = $this->getFileName();
		if (!$this->initPath($path_from)) {
			return FALSE;
		}
		$keyFrom = $this->getFileName();
		return $this->fileSystem->rename($keyFrom, $keyTo);
	}

	/**
	 * Removes a directory
	 */
	public function rmdir($path, $options)
	{
		if (!$this->initPath($path)) {
			return FALSE;
		}
		$filename = $this->getFileName();
		$recursive = $options & STREAM_MKDIR_RECURSIVE;
		if (!$recursive && $this->context !== NULL) {
			$options = stream_context_get_options($this->context);
			$recursive = !empty($options['dir']['recursive']);
		}

		$type = $this->fileSystem->getFileType($filename);
		if ($type !== FileSystem::FILE_TYPE_DIRECTORY) {
			return FALSE;
		} if (!$recursive) {
			$c = $this->fileSystem->isDirectoryEmpty($filename);
			if (!$c) {
				return FALSE;
			}
		}
		return $this->fileSystem->unlinkRecursive($filename);
	}

	/**
	 * Close a resource
	 * @return void
	 */
	public function stream_close()
	{
		$this->dir_closedir();
	}

	/**
	 * Tests for end-of-file on a file pointer
	 */
	public function stream_eof()
	{
		$len = $this->fileSystem->getFileSize($this->getFileName());
		return $this->fpos >= $len;
	}

	/**
	 * Flushes the output
	 */
	public function stream_flush()
	{
		return TRUE;
	}

	private $lockType = null;

	/**
	 * Advisory file locking
	 */
	public function stream_lock($operation)
	{
		$nb = (bool) ($operation & LOCK_NB);
		if ($nb) {
			$operation ^= LOCK_NB;
		}
		$sh = $operation === LOCK_SH;
		$ex = $operation === LOCK_EX;
		$un = $operation === LOCK_UN;

		if ($un && $this->lockType === NULL) {
			return FALSE;
		}
		if ($sh) {
			$lockType = FSLock::LOCK_SH;
		} elseif ($ex) {
			$lockType = FSLock::LOCK_EX;
		} elseif (!$un) {
			return NULL;
		}

		if (!$un and $this->lockType === $lockType)
			return true;


		$fsLock = $this->fileSystem->getLock($nb);
		$timeout = 1000;
		$wait = 200;
		$pokusu = $timeout / $wait;
		do {
			$lock = $fsLock->lock($this->getFileName(), 1000);
			if (!$lock) {
				if (!$un) {
					$this->lockType = NULL;
				}
				trigger_error('Cannot acquire lock', E_USER_NOTICE);
				return FALSE;
			}

			if (!$un && $this->lockType !== NULL && $this->tryRelock($lockType)) {
				$this->lockType = $lockType;
				$fsLock->unlock($lock);
				return TRUE;
			} elseif (!$un and $this->trySetLock($lockType)) {
				$this->lockType = $lockType;
				$fsLock->unlock($lock);
				return TRUE;
			} elseif ($un and $this->tryUnlock()) {
				$this->lockType = NULL;
				$fsLock->unlock($lock);
				return TRUE;
			}
			$fsLock->unlock($lock);
			$delay = mt_rand(floor($wait / 2), $wait);
			usleep($delay);
		} while (!$nb || 0 > $pokusu--);

		return FALSE;
	}

	private function trySetLock($lockType)
	{
		$fileName = $this->getFileName();
		if ($lockType === FSLock::LOCK_EX) {
			return $this->fileSystem->setExclusiveLock($fileName);
		} elseif ($lockType === FSLock::LOCK_SH) {
			return $this->fileSystem->setSharedLock($fileName);
		}
		//unknown lock type
		return FALSE;
	}

	private function tryRelock($lockType)
	{
		return $this->fileSystem->tryRelock($this->getFileName(), $this->lockType, $lockType);
	}

	private function tryUnlock()
	{
		$filename = $this->getFileName();
		if ($this->lockType === FSLock::LOCK_EX) {
			return $this->fileSystem->removeExclusiveLock($filename);
		}
		if ($this->lockType === FSLock::LOCK_SH) {
			return $this->fileSystem->removeSharedLock($filename);
		}
		return FALSE;
	}

	/**
	 * Change stream options
	 */
	public function stream_metadata($path, $option, $value)
	{
		if (!$this->initPath($path)) {
			return FALSE;
		}
		if ($option !== STREAM_META_TOUCH) {
			return FALSE;
		}
		$mtime = isset($value[0]) ? $value[0] : time();
		$atime = isset($value[1]) ? $value[1] : $mtime;
		return $this->fileSystem->touch($this->getFilename(), $mtime, $atime);
	}

	/**
	 * Opens file or URL
	 */
	public function stream_open($path, $mode, $options, &$opened_path)
	{
		if (!$this->initPath($path)) {
			return FALSE;
		}

		$this->mode = preg_replace('/[tb]/', '', $mode); // ignore text/binary mode
		$mode = $this->mode;
		$filename = $this->getFileName();

		if ($mode === 'r' || $mode === 'r+') {
			$ctime = $this->fileSystem->getFileCtime($filename);
			if ($ctime === FALSE) {
				return FALSE;
			}
		} else if ($mode === 'w' || $mode === 'w+') {
			$ctime = $this->fileSystem->getFileCtime($filename);
			if ($ctime === FALSE) {
				if (!$this->createFile()) {
					return FALSE;
				}
			}
			$this->truncateFile();
		} else if ($mode === 'a' || $mode === 'a+') {
			$size = $this->fileSystem->getFileSize($filename);
			if ($size === FALSE) {
				if (!$this->createFile()) {
					return FALSE;
				}
				$size = 0;
			}
			$this->fpos = $size;
		} else if ($mode === 'x' || $mode === 'x+') {
			$ctime = $this->fileSystem->getFileCtime($filename);
			if ($ctime) {
				return FALSE;
			}
			if (!$this->createFile()) {
				return FALSE;
			}
		} else if ($mode === 'c' || $mode === 'c+') {
			$ctime = $this->fileSystem->getFileCtime($filename);
			if ($ctime === FALSE) {
				if (!$this->createFile()) {
					return FALSE;
				}
			}
			$this->fpos = 0;
		} else {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Read from stream
	 */
	public function stream_read($count)
	{
		if (!in_array($this->mode, ['r', 'r+', 'w+', 'a+', 'x+', 'c+'])) {
			return FALSE;
		}
		$data = $this->fileSystem->read($this->getFileName(), $this->fpos, $count);
		if ($data === NULL) {
			return FALSE;
		}
		$this->fpos += strlen($data);
		return $data;
	}

	/**
	 * Seeks to specific location in a stream
	 */
	public function stream_seek($offset, $whence)
	{
		switch ($whence) {
			case SEEK_SET:
				$this->fpos = $offset;
				return TRUE;
			case SEEK_CUR:
				$this->fpos += $offset;
				return TRUE;
			case SEEK_END:
				$size = $this->fileSystem->getFileSize($this->getFileName());
				$this->fpos = intval($size) + $offset;
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * Retrieve information about a file resource
	 */
	public function stream_stat()
	{
		$stat = $this->fileSystem->getStat($this->getFileName());
		return $stat;
	}

	/**
	 * Retrieve the current position of a stream
	 */
	public function stream_tell()
	{
		return $this->fpos;
	}

	/**
	 * Truncate stream
	 */
	public function stream_truncate($new_size)
	{
		return $this->truncateFile($new_size);
	}

	/**
	 * Write to stream
	 */
	public function stream_write($data)
	{
		if (!in_array($this->mode, ['w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'])) {
			return 0;
		}
		//TODO: lock directory to ensure it exists when file is created
		$filename = $this->getFileName();
		$dirname = dirname($filename);
		if (!$this->fileSystem->isDirectory($dirname)) {
			trigger_error("Directory $dirname not exists", E_USER_WARNING);
			return 0;
		}

		$fpos = $this->fpos;
		if (in_array($this->mode, ['a', 'a+'])) {
			$fpos = -1;
		}
		$bytesWritten = $this->fileSystem->write($filename, $fpos, $data);
		if (!in_array($this->mode, ['a', 'a+'])) {
			$this->fpos += $bytesWritten;
		}
		return $bytesWritten;
	}

	/**
	 * Delete a file
	 */
	public function unlink($path)
	{
		if (!$this->initPath($path)) {
			return FALSE;
		}
		$filename = $this->getFileName();
		if (!$this->fileSystem->unlink($filename)) {
			trigger_error("$path is not a file", E_USER_WARNING);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Retrieve information about a file
	 */
	public function url_stat($path, $flags)
	{
		if (!$this->initPath($path)) {
			return array();
		}
		if ($flags & STREAM_URL_STAT_LINK) {
			// stat on the symlinks , not linked files
			if ($flags & STREAM_URL_STAT_QUIET) {
				// do not throw errores
			}
		}
		return $this->stream_stat();
	}

	private function createFile()
	{
		$filename = $this->getFileName();
		$size = $this->fpos;
		if (in_array($this->mode, ['a', 'a+'])) {
			$size = 0;
		}

		return $this->fileSystem->createFile($filename, $size);
	}

	/**
	 * @param int $size
	 * @return bool
	 */
	private function truncateFile($size = 0)
	{
		$filename = $this->getFileName();
		$res = $this->fileSystem->truncateFile($filename, $size);
		if ($res === FALSE) {
			return FALSE;
		}
		if (!in_array($this->mode, ['a', 'a+'])) {
			$this->fpos = $size;
		}
		return TRUE;
	}

	/**
	 * @param string $dirname
	 * @return array
	 */
	private function readDirectory($dirname)
	{
		$dirname = preg_replace('@/$@', '', $dirname);
		return $this->fileSystem->readDirectory($dirname);
	}

	private function initPath($path)
	{
		$url = (object) parse_url($path);
		if (empty($url->scheme)) {
			$matches = NULL;
			if (preg_match('@^([a-z]+)://$@', $path, $matches)) {
				$url->scheme = $matches[1];
				$url->host = '';
			} else {
				return FALSE;
			}
		}
		if (empty(self::$fileSystems[$url->scheme])) {
			return FALSE;
		}
		$this->fileSystem = self::$fileSystems[$url->scheme];
		if (!$this->fileSystem->connect()) {
			return FALSE;
		}
		$filename = '/' . $url->host . '/' . (isset($url->path) ? $url->path : '');
		$this->path = $this->normalize($filename);
		$this->scheme = $url->scheme;

		return TRUE;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function normalize($name)
	{
		$path = rtrim($name, '/');
		$path = preg_replace('@//+@', '/', $path);
		$dirs = explode('/', $path);
		$res = [];
		foreach ($dirs as $d) {
			if ($d === '.') {
				continue;
			}
			if ($d === '..') {
				array_pop($res);
				continue;
			}
			$res[] = $d;
		}
		$path = join('/', $res);
		if (!$path) {
			$path = '/';
		}
		return $path;
	}

	/**
	 * @return string
	 */
	private function getFileName()
	{
		return $this->path;
	}

}
