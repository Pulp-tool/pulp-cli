<?php

namespace Pulp\Fs\Path;

/**
 * Create a relative path that will
 * allow you to get from $from to $to
 */
function relative($from, $to) {
	$fromOrig = realpath($from);
	$toOrig   = realpath($to);


	$from = trim($fromOrig, '/');
	$to   = trim($toOrig,   '/');

	$fromParts = explode('/', $from);
	$toParts   = explode('/', $to);
	$fromLen   = count($fromParts);
	$toLen     = count($toParts);

	for ($x=0; $x < $fromLen; $x++) {
		if ($x == $toLen) {
			//we get here if
			//from: /usr/local/bin
			//to:   /usr/local

			return $fromOrig. '/'. implode('/', array_fill(0, ($fromLen - $x), '..'));
		}
		
		if ($fromParts[$x] == $toParts[$x]) {
			continue;
		}

		//we get here if
		//from: /usr/bin
		//to:   /usr/local/bin
		//or
		//from: /usr/local/lib
		//to:   /usr/local/bin
		break;
	}

	$diff  = $toLen - $fromLen;
	if ($diff < 1) {
		$newPath = $fromOrig. '/'. implode('/', array_fill(0, ( ($fromLen -$x) ), '..'));
		//back-up if toLen is greater than $x
		if ($toLen > $x) {
			$newPath .= '/'.implode('/', array_slice($toParts, $x));
		}
		return $newPath;
	}

	$path = $fromOrig.'/';
	if ($toLen == $fromLen) {
		//we get here if
		//from: /usr/local/bin
		//to:   /usr/local/lib

		$path = $fromOrig. '/'. implode('/', array_fill(0, $fromLen -$x , '..'));
		if ($x > 1) {
			$path .= '/';
		}
	}
	return $path . implode('/', array_slice($toParts, -($toLen - $x)));
}

/**
 * build an absolute path from random parts in order
 *
 * if you give '/usr' '/tmp' 'bar' 'baz.txt'
 *              /tmp/bar/baz.txt
 */
function resolve(...$args) {
	$path = '';
	foreach ($args as $p) {
		if (substr($p,0,1) == '/') {
			$path = $p;
		}
		if (strlen($path) && substr($path,-1) !== '/') {
			$path .= '/';
		}
		$path .= $p;
	}
	return $path;
}

function mkdirp($outputPath) {
	$finfo = new \SplFileInfo($outputPath);
	$pinfo = $finfo->getPathInfo();
	@mkdir($pinfo->getPathname(), 0755, TRUE);
}
