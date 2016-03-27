<?php
namespace Sallyx\StreamWrappers;

include __DIR__ . '/../../vendor/autoload.php';

use Sallyx\StreamWrappers\Redis;
use Sallyx\StreamWrappers\Wrapper;


$fsDir = '/tmp/benchmark';
$redisDir = 'redis://';
$redisPath = 'www.sallyx.org/benchmark::';

$config = new Redis\ConnectorConfig;
$translator = new Redis\PathTranslator($redisPath);
$connector = new Redis\Connector($config, $translator);
$fs = new Redis\FileSystem($connector);
Wrapper::register($fs);

$benchmarks = [
	new FSBenchmark('Filesystem', $fsDir, \DIRECTORY_SEPARATOR),
	new FSBenchmark('Redis', $redisDir, '/'),
];

foreach($benchmarks as $benchmark) {
	$benchmark->run();
	echo PHP_EOL;
}

FSBenchmark::showResults($benchmarks);


class FSBenchmark {

	const TEST_MKDIR = 'mkdir';
	const TEST_RMDIR = 'rmdir';
	const TEST_WRITE1 = 'write_append';
	const TEST_WRITE2 = 'write_big';

	private static $allTests = [ self::TEST_MKDIR, self::TEST_WRITE1, self::TEST_WRITE2, self::TEST_RMDIR];

	/**
	 * @var name
	 */
	private $name;

	/**
	 * @var string
	 */
	private $directory;


	/**
	 * @var string
	 */
	private $directorySeparator;

	/**
	 * @var array[string]string
	 */
	private $results = array();

	/**
	 * @param string $directory
	 */
	public function __construct($name, $directory, $directorySeparator) {
		$this->name = $name;
		$this->directory = $directory;
		$this->directorySeparator = $directorySeparator;
		$this->testRmdir($directory);
		mkdir($directory);
	}

	public function run() {
		echo 'Run test '.self::TEST_MKDIR.PHP_EOL;
		$this->results[self::TEST_MKDIR] =  $this->stopwatch(function() { $this->testMkdir($this->directory,3); });
		
		echo 'Run test '.self::TEST_WRITE1.PHP_EOL;
		$data = str_repeat('abcdefghij', 1);
		$this->results[self::TEST_WRITE1] =  $this->stopwatch(function() use($data) { $this->testWrite('append.bin', $data, 1024); });
		
		echo 'Run test '.self::TEST_WRITE2.PHP_EOL;
		$data = str_repeat('abcdefghij', 1024);
		$this->results[self::TEST_WRITE2] =  $this->stopwatch(function() use($data) { $this->testWrite('big.bin',$data, 1); });

		echo 'Run test '.self::TEST_RMDIR.PHP_EOL;
		$this->results[self::TEST_RMDIR] =  $this->stopwatch(function() { $this->testRmdir($this->directory); });
	}


	private function stopwatch($callable) {
		$start = microtime(TRUE);
		$callable();
		return microtime(TRUE) - $start;
	}

	public function testRmdir($dir) {
		if (is_dir($dir)) { 
			$objects = scandir($dir); 
			foreach ($objects as $object) { 
				if ($object === "." || $object === "..") { 
					continue;
				}
				if (filetype($dir."/".$object) == "dir") {
					$this->testRmdir($dir."/".$object);
				}
			       	else unlink($dir."/".$object); 
			}
			reset($objects); 
			rmdir($dir); 
		}
	}

	/**
	 * @param string $dirname
	 * @param int $deep
	 */
	public function testMkdir($dirname, $deep) {
		if($deep <= 0) return;
		for($i = ord('A'); $i <= ord('J'); $i++) {
			$newdir = $dirname.$this->directorySeparator.chr($i);
			mkdir($newdir);
			$this->testMkdir($newdir, $deep-1);
		}
	}

	/**
	 * @param string $filename
	 * @param string $data
	 * @param int $repeat
	 * @return void
	 */
	public function testWrite($filename, $data, $repeat) {
		$testfile = $this->directory.$this->directorySeparator.$filename;
		for($i = 0; $i < $repeat; $i++) {
			file_put_contents($testfile, $data, FILE_APPEND);
		}
	}

	/**
	 * array[]FSBenchmark
	 */
	public static function showResults(array $benchmarks) {
		echo "\t";
		foreach($benchmarks as $b) {
			echo "\t$b->name";
		}
		echo PHP_EOL;
		foreach(self::$allTests as $test) {
			$min = null;
			$max = null;
			echo $test.":\t";
			foreach($benchmarks as $b) {
				$result =  $b->results[$test];
				printf("\t%2.8f", $result);
				$min = $min == NULL ? $result : min($min, $result);
				$max = $max == NULL ? $result : max($max, $result);
			}
			printf("\t%2.8f", $max/$min);
			echo PHP_EOL;
		}
	}
}
