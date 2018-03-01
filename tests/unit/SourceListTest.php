<?php

class Pulp_SourceListTest extends \PHPUnit\Framework\TestCase { 

	public $rootDir = 'tests/testroot/';

	public function setUp() {
	}

	public function test_source_list_takes_virtualfile() {
		$f = new \Pulp\Fs\VirtualFile('x.txt');
		$y = new \Pulp\Fs\VirtualFile('y.txt');
		$z = new \Pulp\Fs\VirtualFile('z.txt');
//		$p = new \Pulp\Pulp();
		$src = new \Pulp\SourceList(null, [$f,$y,$z]);

		$fname = '';
		$src->on('data', function($file) use (&$fname){
			$fname = $file->getFilename();
		});
		$src->resume();
		$this->assertEquals('x.txt', $fname);

		$src->resume();
		$this->assertEquals('y.txt', $fname);

		$src->resume();
		$this->assertEquals('z.txt', $fname);
	}

	public function test_source_can_read_relative_file() {
		$src = new \Pulp\SourceList(null, 'foo.txt');

		$fname = '';
		$src->on('data', function($file) use (&$fname){
			$fname = $file->getFilename();
		});
		$src->resume();
		$this->assertEquals('foo.txt', $fname);

	}
}
