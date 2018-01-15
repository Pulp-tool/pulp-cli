<?php

namespace Pulp\Fs;

class VirtualFile { // extends \SplFileInfo {

	protected $spl;
	protected $virtual  = FALSE;
	protected $contents = '';


	public function __construct($fname) {
		$this->setPathname($fname);
	}

	public function setContents($str) {
		$this->contents = $str;
	}

	public function getContents() {
		return $this->contents;
	}

	public function setPathname($newPath) {
		$this->spl = new \SplFileInfo($newPath);
	}

	public function __call($name, $args) {
		return call_user_func_array([$this->spl, $name], $args);
	}

	public function __toString() {
		return $this->getFilename();
	}
}
