<?php

namespace Pulp\Fs;

class VirtualFile { // extends \SplFileInfo {

	protected $spl;
	protected $virtual  = FALSE;
	protected $contents = '';
	public    $base     = '';


	public function __construct($fname, $opts=[]) {
		$this->setPathname($fname);
		$pathInfo = $this->getPathInfo();
		$defaults = array_merge(
			[
			'base'=>$pathInfo->getPathname(),
			],
			$opts
		);
		$this->base  = $defaults['base'];
	}

	public function getPartialPathname() {
		return $this->getPartialFilename();
	}

	/**
	 * Return the file name with any path
	 * that is not in the base path
	 */
	public function getPartialFilename() {
		$fullPath = $this->getPathname();
		if ($this->base !== '.') {
			$partial  = str_replace($this->base, '', $fullPath);
		} else {
			$partial = $fullPath;
		}
		return $partial;//$this->getFilename();

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
