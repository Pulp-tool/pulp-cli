<?php

class Pulp_Fs_GlobStreamTest extends \PHPUnit\Framework\TestCase { 


	public function setUp() {
		$this->rootDir = 'tests/testroot/';
	}


	public function test_glob_finds_multiple_star() {
		$fileList         = [];
		$fileListExpected = [
			$this->rootDir.'foo.txt',
			$this->rootDir.'bar.txt'
		];
		$gs = new \Pulp\Fs\GlobStream($this->rootDir.'*.txt');
		$gs->on('data', function($data) use (&$fileList) {
			$fileList[] = $data;
		});
		$gs->findMatchingFiles();
		$this->assertSame($fileList, $fileListExpected);
	}

	public function test_glob_finds_subdir() {
		$fileList         = [];
		$fileListExpected = [
			$this->rootDir.'subd1/baz.txt'
		];
		$gs = new \Pulp\Fs\GlobStream($this->rootDir.'subd1/*.txt');
		$gs->on('data', function($data) use (&$fileList) {
			$fileList[] = $data;
		});
		$gs->findMatchingFiles();
		$this->assertSame($fileList, $fileListExpected);
	}

	public function test_glob_finds_doublestar() {
		$fileList         = [];
		$fileListExpected = [
			$this->rootDir.'foo.txt',
			$this->rootDir.'bar.txt',
			$this->rootDir.'subd1',
			$this->rootDir.'subd1/baz.txt'
		];
		$gs = new \Pulp\Fs\GlobStream($this->rootDir.'**/*.txt');
		$gs->on('data', function($data) use (&$fileList) {
			$fileList[] = $data;
		});
		$gs->findMatchingFiles();
		$this->assertSame($fileList, $fileListExpected);
	}

}
