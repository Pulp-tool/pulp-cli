<?php

class Pulp_Fs_VirtualFileTest extends \PHPUnit\Framework\TestCase { 


	public function setUp() {
	}


	public function test_get_contents_virtual_file() {
		$vcontents = 'abc';
		$f = new \Pulp\Fs\VirtualFile('/nonexistant.ext');
		$f->setContents($vcontents);
		$this->assertEquals($vcontents, $f->getContents());
	}

	public function test_get_path_virtual_file() {
		$expected = '/usr/bin';
		$f = new \Pulp\Fs\VirtualFile('/usr/bin/nonexistant.ext');
		$this->assertEquals($expected, $f->getPath());

		//doesn't match with php docs
		$d = new \Pulp\Fs\VirtualFile('/usr/bin/php');
		$this->assertEquals($expected, $d->getPath());
	}

	public function test_get_partial_filename_never_starts_with_slash() {
		$expected = 'site.css';
		$f = new \Pulp\Fs\VirtualFile('/usr/bin/site.css');
		$this->assertEquals($expected, $f->getPartialFilename());

		$this->assertNotEquals('/'.$expected, $f->getPartialFilename());

		$f = new \Pulp\Fs\VirtualFile('/site.css', ['base'=>'.']);
		$this->assertEquals($expected, $f->getPartialFilename());

		$this->assertNotEquals('/'.$expected, $f->getPartialFilename());

		$f = new \Pulp\Fs\VirtualFile('/site.css', ['base'=>'/']);
		$this->assertEquals($expected, $f->getPartialFilename());

		$this->assertNotEquals('/'.$expected, $f->getPartialFilename());
	}
}
