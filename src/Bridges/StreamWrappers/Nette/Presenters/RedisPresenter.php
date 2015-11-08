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
		$this->dir = rtrim($dir,'/').'/';
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
		$dir = $this->scheme . ':/' . $this->dir;
		$this->returnIfFile($dir);

		$handle = opendir($dir);
		while ($name = readdir($handle)) {
			if($name == '.') {
				$path = $this->dir;
			} elseif ($name == '..') {
				$path = dirname($this->dir);
			} else {
				$path = $this->dir.$name;
			}

			$file = $dir.$name;
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
		$dir = $this->scheme . ':/' . $this->dir;
		$this->returnIfFile($dir);
		$handle = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

		foreach ($handle as $file => $info) {
			$path = substr($file, strlen($this->scheme)+2);
			if(substr($path,-3) === '/..') {
				continue;
			}
			if(substr($path,-2) === '/.') {
				$path = substr($path, 0, -2);
			}
			$lock_sh = $this->fileSystem->getFileSharedLocksCount($path);
			$lock_ex = $this->fileSystem->hasFileExclusiveLock($path) ? '1' : '0';
			if(!$lock_sh && !$lock_ex) continue;
			$name = $path;
			$files[$file] = (object) array(
					'file' => $file,
					'path' => $path,
					'name' => $name,
					'stat' => lstat($file),
					'filetype' => filetype($file),
					'lock_sh' => $lock_sh,
					'lock_ex' => $lock_ex
			);
		}

		ksort($files, SORT_NATURAL);
		$this->template->scheme = $this->scheme;
		$this->template->dir = $this->dir;
		$this->template->files = $files;
	}

}
