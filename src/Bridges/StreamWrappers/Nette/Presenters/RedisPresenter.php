<?php

namespace SallyxBridgesStreamWrappersNettePresentersModule;

use Nette\Application\UI\Presenter;
use Sallyx\StreamWrappers\FileSystem;
use Sallyx\StreamWrappers\Wrapper;

/**
 * Description of RedisPresenter
 *
 * @author petr
 */
class RedisPresenter extends Presenter
{

	/**
	 * @var string
	 * @persistent
	 */
	public $scheme;

	/**
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * @var FileSystem
	 */
	private $fileSystem;

	public function actionDefault($scheme, $dir)
	{
		if (preg_match('/^[a-z0-9]+$/i', $this->scheme)) {
			$this->scheme = $scheme;
			$this->fileSystem = Wrapper::getRegisteredWrapper($this->scheme);
		}
		$this->dir = trim($dir, '/');
		if (!($this->fileSystem instanceof FileSystem)) {
			echo 'Unkown filesystem ' . $this->scheme . '://. Is it registered?';
			$this->terminate();
		}
	}

	public function actionLocked($scheme, $dir)
	{
		$this->actionDefault($scheme, $dir);
	}

	private function returnIfFile($dirname)
	{
		if (is_file($dirname)) {
			$response = new \Nette\Application\Responses\FileResponse($dirname);
			$this->sendResponse($response);
			$this->terminate();
		}
	}

	public function renderDefault()
	{
		$files = array();
		$dir = $this->scheme . '://' . $this->dir;
		$this->returnIfFile($dir);

		$handle = opendir($dir);
		while ($file = readdir($handle)) {
			$path = '/' . substr($file, strlen($this->scheme) + 3);
			if ($dir === $file) {
				$name = '.';
			} else {
				$name = $dir < $file ? basename($path) : '..';
			}

			$files[$file] = (object) array(
					'file' => $file,
					'path' => $path,
					'name' => $name,
					'stat' => lstat($file),
					'filetype' => filetype($file),
					'lock_sh' => $this->fileSystem->getFileSharedLocksCount($path),
					'lock_ex' => $this->fileSystem->hasFileExclusiveLock($path) ? '1' : '0'
			);
		}

		ksort($files, SORT_NATURAL);
		$this->template->scheme = $this->scheme;
		$this->template->dir = $this->dir;
		$this->template->files = $files;
	}

	public function renderLocked()
	{
		$files = array();
		$dir = $this->scheme . '://' . $this->dir;
		$this->returnIfFile($dir);
		//TODO: recursive directory iterator not working
		$handle = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::CURRENT_AS_FILEINFO);

		foreach ($handle as $file => $stat) {
			var_dump('ajaj', $file, $stat);
			$path = '/' . substr($file, strlen($this->scheme) + 3);
			if ($dir === $file) {
				$name = '.';
			} else {
				$name = $dir < $file ? basename($path) : '..';
			}

			$files[$file] = (object) array(
					'file' => $file,
					'path' => $path,
					'name' => $name,
					'stat' => lstat($file),
					'filetype' => filetype($file),
					'lock_sh' => $this->fileSystem->getFileSharedLocksCount($path),
					'lock_ex' => $this->fileSystem->hasFileExclusiveLock($path) ? '1' : '0'
			);
		}

		ksort($files, SORT_NATURAL);
		$this->template->scheme = $this->scheme;
		$this->template->dir = $this->dir;
		$this->template->files = $files;
	}

}