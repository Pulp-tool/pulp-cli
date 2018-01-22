<?php

class Pulp_Fs_PathTest extends \PHPUnit\Framework\TestCase { 

	public $rootDir = 'tests/testroot/';

	public function setUp() {
	}

	public function test_relative_goes_down() {
		$expected =  getcwd().'/./tests/';
		$x = \Pulp\Fs\Path\relative( getcwd(), $expected);
		$this->assertEquals(realpath($expected), $x);
	}

	public function test_relative_goes_down_twice() {
		$expected =  getcwd().'/./tests/unit/';
		$x = \Pulp\Fs\Path\relative( getcwd(), $expected);
		$this->assertEquals(realpath($expected), $x);
	}

	public function test_relative_goes_up() {
		$expected = '/usr/local/bin/..';
		$x = \Pulp\Fs\Path\relative( '/usr/local/bin/', '/usr/local/' );
		$this->assertEquals($expected, $x);
	}

	public function test_relative_goes_up_then_down() {
		$expected = '/usr/local/lib/../bin';
		$x = \Pulp\Fs\Path\relative( '/usr/local/lib', '/usr/local/bin' );
		$this->assertEquals($expected, $x);
	}

	public function test_relative_goes_up_then_down_more_than_once() {
		$expected = getcwd().'/tests/unit/../../src';
		$x = \Pulp\Fs\Path\relative( getcwd().'/tests/unit/', getcwd().'/src/' );
		$this->assertEquals($expected, $x);
	}
}
