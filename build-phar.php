#!/usr/bin/env php
<?php

$srcRoot    = "src";
$vendorRoot = "vendor";
$buildRoot  = "./build";
$pharFile   = $buildRoot.'/pulp.phar';

exec("rm $pharFile");
 
$phar = new Phar($pharFile, 
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    "pulp.phar");

$phar->startBuffering();

$dirRootList = [ $srcRoot, $vendorRoot ];
foreach ($dirRootList as $_root) {
	$d = new RecursiveDirectoryIterator($_root);
	$i = new RecursiveIteratorIterator($d, RecursiveIteratorIterator::SELF_FIRST);
	$count = 0;
	foreach ($i as $file) {
		if ( 0 == strpos( $file->getFilename(), '.')) { continue; }
		if ( FALSE !== strpos( $file->getFilename(), '.xml.dist')) { continue; }
		if ( FALSE !== strpos( $file->getPathName(), 'README.md')) { continue; }
		if ( FALSE !== strpos( $file->getPathName(), 'tests/')) { continue; }
		if ( FALSE !== strpos( $file->getPathName(), 'examples/')) { continue; }
		$count++;
		echo "Processing: ".$file->getPathName()."\n";
		$phar->addFromString($file->getPathName(), file_get_contents($file));
	}
}
echo "Packaging ".$count." files.\n";

//pack the main bin file
$content = file_get_contents(__DIR__."/bin/pulp");
$content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
$phar->addFromString('pulp', $content);


$stub = <<<EOL
#!/usr/bin/env php
<?php
Phar::mapPhar('pulp.phar');
set_include_path( 'phar://pulp.phar/' . PATH_SEPARATOR . get_include_path() );
require 'phar://pulp.phar/pulp';
__HALT_COMPILER();
EOL;
$phar->setStub($stub);

$phar->stopBuffering();
echo "Generated $pharFile \n";
unset($phar);
chmod($pharFile, 0755);
echo "File size is ".round(filesize($pharFile) / 1024 / 1024, 2)." MB.\n";
echo "Complete!\n";
