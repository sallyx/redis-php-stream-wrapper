<?php

namespace Sallyx\StreamWrappers;

interface FileSystem
{

	const FILE_TYPE_DIRECTORY = 'd';
	const FILE_TYPE_FILE = 'f';

	/**
	 * Init file system resources
	 * @return bool
	 */
	public function connect();

	/**
	 * Close file system resources
	 * @return bool
	 */
	public function disconnect();

	/**
	 * @param string $dirname
	 * @return \Iterator
	 */
	public function getDirectoryIterator($dirname);

	/**
	 * @param string $filename
	 * @return string|FALSE Return FILE_TYPE_* or FALSE on error
	 */
	public function getFileType($filename);

	/**
	 * @param string $filename
	 * @return int|FALSE
	 */
	public function getFileCtime($filename);

	/**
	 * @param string $filename
	 * @return int|FALSE
	 */
	public function getFileSize($filename);

	/**
	 * @param string $dirname
	 * @return bool
	 */
	public function isDirectory($dirname);

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function isFile($filename);

	/**
	 * @param string $keyFrom
	 * @param string $keyTo
	 * @return bool
	 */
	public function rename($keyFrom, $keyTo);

	/**
	 * @param string $filename
	 * @param int $fpos
	 * @param int $count
	 * @return string|NULL
	 */
	public function read($filename, $fpos, $count);

	/**
	 * @param string $filename
	 * @param int $fpos Where to start to write. $fpos < 0 == at the end
	 * @param string $data
	 * @return int Bytes written
	 */
	public function write($filename, $fpos, $data);

	/**
	 * @param string $filename
	 * @param int $size
	 * @return array|FALSE
	 */
	public function createFile($filename, $size = 0);

	/**
	 * @param string $filename
	 * @param int $size
	 * @return boolean
	 */
	public function truncateFile($filename, $size = 0);

	/**
	 * @param string $filename
	 * @return array|FALSE
	 */
	public function createDirectory($filename);

	/**
	 * @param type $dirname
	 * @return array|FALSE
	 */
	public function readDirectory($dirname);

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function isDirectoryEmpty($filename);

	/**
	 * Remove directory and all its files
	 * @param string $filename
	 * @return bool
	 */
	public function unlinkRecursive($dirname);

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function unlink($filename);

	/**
	 * @param string $filename
	 * @return array|NULL
	 */
	public function getStat($filename);

	/**
	 * @param bool $nb Non-blocking mode?
	 * @return FSLock
	 */
	public function getLock($nb);

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function hasFileExclusiveLock($filename);

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function setExclusiveLock($filename);

	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function setSharedLock($filename);

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function removeExclusiveLock($filename);

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function removeSharedLock($filename);

	/**
	 * @param string $filename
	 * @param string $from
	 * @param strin $to
	 * @return boolean
	 */
	public function tryRelock($filename, $from, $to);
}
