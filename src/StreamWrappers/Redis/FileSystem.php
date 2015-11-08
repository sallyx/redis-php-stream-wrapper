<?php

namespace Sallyx\StreamWrappers\Redis;

use Sallyx\StreamWrappers\FileSystem as FS;
use Sallyx\StreamWrappers\FSLock;

class FileSystem implements FS
{

	/**
	 * @var Connector
	 */
	private $connector;

	/**
	 * @var Storage
	 */
	private $storage;

	public function __construct(Connector $connector)
	{
		$this->connector = $connector;
	}

	/**
	 * @return bool
	 */
	public function connect()
	{
		$this->storage = $this->connector->connect();
		if ($this->storage === NULL) {
			return FALSE;
		}
		$dirname = '/';
		if (!$this->isDirectory($dirname)) {
			return !!$this->createDirectory($dirname);
		}
		return TRUE;
	}

	/**
	 * @return bool
	 */
	public function disconnect()
	{
		return $this->connector->disconnect();
	}

	/**
	 * @param string $dirname
	 * @return \Iterator
	 */
	public function getDirectoryIterator($dirname)
	{
		$files = array();
		$parent = dirname($dirname);
		if($parent && $parent !== $dirname) {
			$files = array('..');
		}
		$files = array_merge($files, $this->storage->getDirectoryFiles($dirname));
		if (!is_array($files)) {
			return NULL;
		}
		sort($files, SORT_NATURAL);
		return new \ArrayIterator($files);
	}

	/**
	 * @param string $filename
	 * @return string|FALSE
	 */
	public function getFileType($filename)
	{
		return $this->storage->getFileProperty($filename, 'type');
	}

	/**
	 * @param string $filename
	 * @return int|FALSE
	 */
	public function getFileCtime($filename)
	{
		return $this->storage->getFileProperty($filename, 'ctime');
	}

	/**
	 * @param string $filename
	 * @return int|FALSE
	 */
	public function getFileSize($filename)
	{
		return $this->storage->getFileProperty($filename, 'size');
	}

	/**
	 * @param string $dirname
	 * @return bool
	 */
	public function isDirectory($dirname)
	{
		return $this->getFileType($dirname) === FileSystem::FILE_TYPE_DIRECTORY;
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function isFile($filename)
	{
		return $this->getFileType($filename) === FileSystem::FILE_TYPE_FILE;
	}

	/**
	 * @param string $filenameFrom
	 * @param string $filenameTo
	 * @return bool
	 */
	public function rename($filenameFrom, $filenameTo)
	{
		$dirname = dirname($filenameTo);
		if (!$dirname) {
			$dirname = '/';
		}
		$keys = array($filenameFrom, $filenameTo, $dirname, basename($filenameFrom));
		return $this->storage->evaluate("
			local dir_type, file_type, newName;
			dir_type = redis.call('HGET',KEYS[3], 'type');
			file_type = redis.call('HGET',KEYS[2],'type');
			if dir_type ~= 'd' then return redis.error_reply('Directory '..KEYS[3]..' not exists'); end;
			newName = KEYS[2];
			if file_type == 'd'
			then
				newName = newName..'/'..ARGV[1];
				file_type = redis.call('HGET', newName, 'type');
				if file_type ~= 'f' and file_type ~= false then return redis.error_reply('File '..newName..' exists ') end;
			end;
			if not redis.call('EXISTS', KEYS[1]) then return redis.error_reply('Source file not exists'); end;
			return redis.call('RENAME', KEYS[1], newName);
		", $keys, count($keys) - 1);
	}

	/**
	 * @param string $filename
	 * @param int $fpos
	 * @param int $count
	 * @return string|NULL
	 */
	public function read($filename, $fpos, $count)
	{
		$file = $this->storage->getFileProperties(
			$filename, array('type', 'content')
		);
		if (empty($file)) {
			return NULL;
		}
		if ($file['type'] !== FileSystem::FILE_TYPE_FILE) {
			return NULL;
		}

		return substr($file['content'], $fpos, $count);
	}

	/**
	 * @param string $filename
	 * @param int $fpos Where to start to write. $fpos < 0 == at the end
	 * @param string $data
	 * @return int Bytes written
	 */
	public function write($filename, $fpos, $data)
	{
		$value = $this->storage->getFile($filename);
		if (empty($value)) {
			$value = array(
				'type' => FileSystem::FILE_TYPE_FILE,
				'ctime' => time(),
				'atime' => time(),
				'mtime' => time(),
				'content' => $fpos < 0 ? '' : str_repeat("\0", $fpos),
				'size' => $fpos < 0 ? 0 : $fpos
			);
		}

		if ($fpos < 0) {
			$fpos = $value['size'];
		}

		$dataLen = strlen($data);

		if ($fpos >= $value['size']) {
			$value['content'] .= str_repeat("\0", $fpos - $value['size']) . $data;
		} else {
			$value['content'] = substr($value['content'], 0, $fpos) . $data . substr($value['content'], $fpos + $dataLen);
		}

		$value['atime'] = $value['mtime'] = time();
		$value['size'] = strlen($value['content']);
		if (!$this->storage->setFileProperties($filename, $value)) {
			return 0;
		}

		return $dataLen;
	}

	private function createInode($filename, $type, $content)
	{
		if ($filename !== '/') {
			$dirname = dirname($filename);

			if ($dirname && !$this->isDirectory($dirname)) {
				return FALSE;
			}
		}

		if (!$this->storage->createFileWithProperty($filename, 'ctime', time())) {
			return FALSE;
		}
		$value = array(
			'type' => $type,
			'ctime' => time(),
			'atime' => time(),
			'mtime' => time(),
			'content' => $content,
			'size' => strlen($content),
			'lock_ex' => 0,
			'lock_sh' => 0,
		);
		if (!$this->storage->setFileProperties($filename, $value)) {
			return FALSE;
		}
		return $value;
	}

	/**
	 * @param string $filename
	 * @param int $size
	 * @return array|FALSE
	 */
	public function createFile($filename, $size = 0)
	{
		$content = str_repeat("\0", $size);
		return $this->createInode($filename, FileSystem::FILE_TYPE_FILE, $content);
	}

	/**
	 * @param string $filename
	 * @param int $size
	 * @return boolean
	 */
	public function truncateFile($filename, $size = 0)
	{
		if ($size < 0) {
			$size = 0;
		}
		if ($size == 0) {
			$content = '';
		} else {
			$content = $this->storage->getFileProperty($filename, 'content');
			if ($content === FALSE) {
				return FALSE;
			}
		}
		$content = substr($content, 0, $size);
		$content .= str_repeat("\0", $size - strlen($content));

		$value = array(
			'atime' => time(),
			'mtime' => time(),
			'content' => $content,
			'size' => $size
		);
		return $this->storage->setFileProperties($filename, $value);
	}

	/**
	 * @param string $filename
	 * @return array|FALSE
	 */
	public function createDirectory($filename)
	{
		return $this->createInode($filename, FileSystem::FILE_TYPE_DIRECTORY, NULL);
	}

	/**
	 * @param type $dirname
	 * @return array|FALSE
	 */
	public function readDirectory($dirname)
	{
		$directory = $this->storage->getFile($dirname);

		if (!empty($directory) && isset($directory['type'])) {
			if ($directory['type'] === FS::FILE_TYPE_DIRECTORY) {
				return $directory;
			}
			return FALSE;
		}
		return FALSE;
	}

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function isDirectoryEmpty($filename)
	{
		$it = NULL;
		$c = 0;
		do {
			$keys = $this->storage->scanDirectory($filename, 100, $it);
			$c += count($keys);
			if (in_array($filename, $keys)) {
				$c--;
			}
			if ($c > 1)
				return FALSE;
		} while ($it !== 0);
		return TRUE;
	}

	/**
	 * Remove directory and all its files
	 * @param string $dirname
	 * @return bool
	 */
	public function unlinkRecursive($dirname)
	{
		return 1 === $this->storage->evaluate("for _,k in ipairs(redis.call('keys',KEYS[1]..'*')) do redis.call('del',k) end; return 1", array($dirname), 1);
	}

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function unlink($filename)
	{
		$fileType = $this->getFileType($filename);
		if ($fileType !== FileSystem::FILE_TYPE_FILE) {
			return FALSE;
		}
		return $this->storage->del($filename) === 1;
	}

	/**
	 * @param string $filename
	 * @return array|NULL
	 */
	public function getStat($filename)
	{
		$file = $this->storage->getFileProperties($filename, array('size', 'atime', 'mtime', 'ctime', 'type', 'lock_sh', 'lock_ex'));

		if ($file['ctime'] === FALSE) {
			return NULL;
		}
		$mode = 0;
		if ($file['type'] === FileSystem::FILE_TYPE_FILE) {
			$mode = 0100666;
		} else if ($file['type'] === FileSystem::FILE_TYPE_DIRECTORY) {
			$mode = 040777;
		}

		$values = array(
			'dev' => 0, //TODO: dbindex
			'ino' => 0,
			'mode' => $mode,
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => 0,
			'size' => $file['size'],
			'atime' => $file['atime'],
			'mtime' => $file['mtime'],
			'ctime' => $file['ctime'],
			'blksize' => -1,
			'blocks' => -1,
			//extra properites
			'lock_sh' => $file['lock_sh'],
			'lock_ex' => $file['lock_ex']
		);

		return array_merge(array_values($values), $values);
	}

	/**
	 * @param bool $nb Non-blocking mode?
	 * @return FSLock
	 */
	public function getLock($nb)
	{
		return $this->storage->getLock($nb);
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function hasFileExclusiveLock($filename)
	{
		$filename = rtrim($filename, '/');
		$filename = $filename ?: '/';
		return "1" === $this->storage->getFileProperty($filename, 'lock_ex');
	}

	/**
	 * @param string $filename
	 * @return int
	 */
	public function getFileSharedLocksCount($filename)
	{
		$filename = rtrim($filename, '/');
		$filename = $filename ?: '/';
		return $this->storage->getFileProperty($filename, 'lock_sh');
	}

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function setExclusiveLock($filename)
	{
		$lock = $this->storage->getFileProperties($filename, array('lock_sh', 'lock_ex'));
		if ($lock['lock_sh'] !== "0" || $lock['lock_ex'] !== "0") {
			return FALSE;
		}
		if (!$this->storage->incrFilePropertyBy($filename, 'lock_ex', 1)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function setSharedLock($filename)
	{
		$lock_ex = $this->hasFileExclusiveLock($filename);
		if ($lock_ex) {
			return FALSE;
		}
		if (!$this->storage->incrFilePropertyBy($filename, 'lock_sh', 1)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function removeExclusiveLock($filename)
	{
		return $this->storage->setFileProperty($filename, 'lock_ex', 0) !== FALSE;
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function removeSharedLock($filename)
	{
		return $this->storage->incrFilePropertyBy($filename, 'lock_sh', -1) !== FALSE;
	}

	/**
	 * @param string $filename
	 * @param string $from
	 * @param strin $to
	 * @return boolean
	 */
	public function tryRelock($filename, $from, $to)
	{
		if ($from === $to) {
			return TRUE;
		}

		if ($from === FSLock::LOCK_EX) {
			if ($to !== FSLock::LOCK_SH) {
				return FALSE;
			}
			if (!$this->removeExclusiveLock($filename)) {
				return FALSE;
			}
			return $this->storage->incrFilePropertyBy($filename, 'lock_sh', 1) !== FALSE;
		}

		if ($from == FSLock::LOCK_SH) {
			if ($to !== FSLock::LOCK_EX) {
				return FALSE;
			}
			$lock = $this->storage->getFileProperties($filename, array('lock_sh', 'lock_ex'));
			if ($lock['lock_sh'] !== "0" || $lock['lock_ex'] !== "1") {
				return FALSE;
			}
			if (!$this->removeSharedLock($filename)) {
				return FALSE;
			}

			return $this->storage->incrFilePropertyBy($filename, 'lock_ex', 1) !== FALSE;
		}

		return FALSE;
	}

	/**
	 * @return boolean
	 */
	private function fileExists($filenamea)
	{
		return FALSE !== $this->getFileType($filename);
	}

	/**
	 * Change file $mtime and $atime.
	 * Create if not exists.
	 * @param strting $filename
	 * @param string $mtime
	 * @param string $atime
	 * @return boolean
	 */
	public function touch($filename, $mtime, $atime)
	{
		$fileType = $this->getFileType($filename);
		if ($fileType === FALSE) {
			if (!$this->createFile($filename)) {
				trigger_error("Cannot create file '$filename'", E_USER_WARNING);
				return FALSE;
			}
		}
		return $this->storage->setFileProperties($filename, ['mtime' => $mtime, 'atime' => $atime]);
	}

}
