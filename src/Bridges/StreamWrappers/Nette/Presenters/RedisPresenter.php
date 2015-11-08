<?php
namespace SallyxBridgesStreamWrappersNettePresentersModule;

use  Nette\Application\UI\Presenter;
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
	 * @var striing
	 */
	private $scheme = 'redis';

	/**
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * @var FileSystem
	 */
	private $fileSystem;

	public function actionDefault($scheme, $dir) {
		if(preg_match('/^[a-z0-9]+$/i', $this->scheme)) {
			$this->scheme = $scheme;
			$this->fileSystem = Wrapper::getRegisteredWrapper($this->scheme);
		}
		$this->dir = trim($dir,'/');
	}

	public function renderDefault() {
		if(!($this->fileSystem  instanceof FileSystem)) {
			echo 'Unkown filesystem '.$this->scheme.'://. Is it registered?';
			$this->terminate();

		}
		$files = array();
		$dir = $this->scheme.'://'.$this->dir;
		$handle = opendir($dir);
		while($file = readdir($handle)) {
			$path = substr($file, strlen($this->scheme) + 3);
			$files[$file] = (object) array(
				'file' => $file,
				'path' => $path,
				'stat' => lstat($file),
				'filetype' => filetype($file),
				'lock_sh' => $this->fileSystem->getFileSharedLocksCount($path),
				'lock_ex' => $this->fileSystem->hasFileExclusiveLock($path)
			);

		}

		ksort($files, SORT_NATURAL);
		$this->template->scheme = $this->scheme;
		$this->template->dir = $this->dir;
		$this->template->files = $files;
	}
}
