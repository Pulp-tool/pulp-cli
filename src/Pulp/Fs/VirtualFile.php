<?php

namespace Pulp\Fs;

class VirtualFile { // extends \SplFileInfo {

	protected $spl;
	protected $virtual  = FALSE;
	protected $contents = '';
	public    $base     = '';


	public function __construct($fname, $opts=[]) {
		$this->setPathname($fname, $opts);
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
		if ($this->base === '.') {
			$partial = $fullPath;
		} else {
			if (substr($this->base, 0, 2) == './') {
				$b = str_replace('./', '', $this->base);
			} else {
				$b = $this->base;
			}
			$partial  = str_replace($b, '', $fullPath);
		}
		//partials should never begin with /
		$partial = ltrim($partial, '/');
		return $partial;//$this->getFilename();

	}

	public function setContents($str) {
		$this->contents = $str;
	}

	public function getContents() {
		return $this->contents;
	}

	public function setPathname($newPath, $opts=[]) {
		$this->spl = new \SplFileInfo($newPath);
		$pathInfo = $this->getPathInfo();
		$defaults = array_merge(
			[
			'base'=>$pathInfo->getPathname(),
			],
			$opts
		);
		$this->base  = $defaults['base'];
	}

	public function __call($name, $args) {
		return call_user_func_array([$this->spl, $name], $args);
	}

	public function __toString() {
		return $this->getFilename();
	}
}
