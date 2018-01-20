Install
===
Download the phar file, make it executable and move it to /usr/local/bin/pulp
```bash
wget https://github.com/Pulp-tool/pulp-cli/releases/download/0.12.0/pulp.phar
chmod a+x pulp.phar
mv pulp.phar /usr/local/bin/pulp
```

Initialize your project
===
From your project root:

```bash
pulp init-project
git add .pulp/
git commit -m "Adding pulp build tool"
cd .pulp
composer install
```

Use
===
Create tasks and watchers, pipe data from src() to dest()

```php
$p = new \Pulp\Pulp();

//The log task is a dependency of the build task.
//This will run the log task before running the build task

$p->task('build', ['log'], function() {
	echo "Building...\n";
});


//The log task is a regular closure

$foo = time();
$p->task('log', function() use ($foo) {
	echo "Logging with variable: $foo...\n";
});

//The watch method will either poll or use inotify to detect file changes

$p->task('wait-and-pipe', function() use($p) {
	$p->watch( ['src/**/*.php', 'foo/**/*.php'])->on('change', function($file) use ($p) {
		//this will pipe *all* files in src/ and foo/, not just the ones that changed
		$p->src(['src/', 'foo/'])
			->pipe(new \Pulp\DataPipe(function($data, $pipe) {
				echo "Data Pipe got "; var_dump($data);
				$pipe->push($data); //propagate data down the chain
			}))
			->pipe(new \Pulp\DataPipe(null, function($data, $pipe) {
				// the second parameter is for end even handlers
				echo "I'm only called once \n";
			})

		);
			
		echo "This file triggered the change event: ".$file." ...\n";
	});
});
```

```bash
pulp build
pulp watch
```

Compile Less
===
```php

$p = new \Pulp\Pulp();

function compileLess($p) {
	return $p->src(['foo/bootstrap.less'])
	  ->pipe(new \Pulp\Debug())                     //will simply print every file from src
	  ->pipe(new \Pulp\Less( ['compress'=>TRUE] ))  //renames files from *.less to *.css
	  ->pipe($p->dest('foo/'));                     //will output foo/bootstrap.css

}
$p->task('less-now', function() use($p) {
	compileLess($p)
});

$p->task('less', function() use($p) {
	compileLess($p);  //start by refreshing once
	$p->watch( ['foo/**/*.less'])->on('change', function($file) use ($p, $lr) {
		compileLess($p);
	});
});

```

```bash
[11:26:25] Starting task 'less'
[11:26:25] Finished task 'less' (took: 9.230 ms)
[11:26:25] pulp-debug: bootstrap.less
[11:26:29] pulp-debug: bootstrap.less
```


Compile Less with LiveReload
===
```php

$p = new \Pulp\Pulp();
$lr = new \Pulp\LiveReload();

function compileLess($p) {
	return $p->src(['foo/bootstrap.less'])
	  ->pipe(new \Pulp\Debug())                     //will simply print every file from src
	  ->pipe(new \Pulp\Less( ['compress'=>TRUE] ))  //renames files from *.less to *.css
	  ->pipe($p->dest('foo/'));                     //will output foo/bootstrap.css

}

$p->task('less', function() use($p) {
	$lr = new lr();
	$lr->listen($p->loop);

	compileLess($p);  //start by refreshing once
	$p->watch( ['foo/**/*.less'])->on('change', function($file) use ($p, $lr) {
		$stream = compileLess($p);
		return $stream->pipe($lr);
	});
});

```

```bash
[11:27:11] Starting task 'less'
[11:27:11] Finished task 'less' (took: 9.230 ms)
[11:27:11] pulp-debug: bootstrap.less
[11:27:15] pulp-debug: bootstrap.less
[11:27:15] Sending reload because file changed: bootstrap.css
```

Compile your own Phar
===
```php

$pharSettings = [
	'alias'      => 'myscript-'.time().'.phar',
	'srcRoot'    => "src/**",
	'vendorRoot' => "vendor/**",
	'buildRoot'  => "build/",
	'pharFile'   => "build/myscript.phar",
	'entryPoint' => 'bin/myscript.php',
];

$p->task('clean-phar', function() use($pharSettings, $p) {
	$pharFile = $pharSettings['pharFile'];
	return @exec("rm ". $pharFile);
});

$p->task('build-phar', ['clean-phar'], function() use($p, $pharSettings) {
	$pharFile  = $pharSettings['pharFile'];
	$pharAlias = $pharSettings['alias'];
	$buildRoot = $pharSettings['buildRoot'];
	$count     = 0;

	@exec("mkdir ".$buildRoot);

	$phar = new Phar($pharFile,
		FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
		$pharAlias
	);

	$phar->startBuffering();

	$dirList = [ $pharSettings['srcRoot'], $pharSettings['vendorRoot'] ];
	return $p->src($dirList)
		->pipe(new \Pulp\DataPipe(function($file, $pipe) use(&$count, $phar) {
			$count++;
			$pipe->emit('log', ["Adding to phar build: ".$file->getPathName()]);
			$phar->addFromString($file->getPathname(), file_get_contents($file->getPathname()));
			$pipe->push($file);
		}))
		->pipe(new \Pulp\DataPipe(NULL, function($pipe) use(&$count, $phar, $pharFile, $pharStub) {
			$pipe->log("Packaging %d files.", [$count]);

			//pack the main bin file
			$content = file_get_contents($pharSettings['entryPoint']);
			$content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
			$phar->addFromString($pharSettings['entryPoint'], $content);

			$phar->createDefaultStub($pharSettings['entryPoint']);
			$phar->stopBuffering();

			$pipe->log("Generated <file>$pharFile</>");

			unset($phar);
			chmod($pharFile, 0755);
			$pipe->log("File size is ".round(filesize($pharFile) / 1024 / 1024, 2)." MB.");
		}));
});
```

```bash
[12:06:09] Starting task 'clean-phar'
[12:06:09] Finished task 'clean-phar' (took: 2.671 ms)
[12:06:09] Starting task 'build-phar'
[12:06:09] Adding to phar build: vendor/autoload.php
[12:06:09] Adding to phar build: vendor/react/stream/CHANGELOG.md
[12:06:09] Adding to phar build: vendor/react/stream/src/ThroughStream.php
[12:06:09] Adding to phar build: vendor/react/stream/src/CompositeStream.php
[12:06:09] Adding to phar build: vendor/react/stream/src/DuplexResourceStream.php
[12:06:09] Adding to phar build: vendor/react/stream/src/ReadableStreamInterface.php 
[12:06:09] Adding to phar build: vendor/react/stream/src/WritableResourceStream.php
[12:06:09] Adding to phar build: src/your-src-files-here.php
...
[12:06:09] Packaging 50 files.
[12:06:09] Generated build/myscript.phar
[12:06:09] File size is 0.16 MB.
[12:06:09] Finished task 'build-phar' (took: 34.887 ms)
```


Setup your project
===
Run pulp init-project to create a .pulp directory, a config.php file and a composer.json file.

```bash
your-project/
  .pulp/            # <== created with pulp init-project
     config.php     # <== tasks go here
     composer.json  # <== pulp plugins go here
  src/
    controllers/
  public/
    templates/
      styles/
        my-project.less
```

CD into the .pulp directory and run *composer install*

Edit config.php and create your own build pipeline


$p Pulp object
===
| method|
| -------- |
| `src( array [$srcGlob [, $srcGlob, ...]], callback fn($file) )`
|  A string or array of strings that point to source files.  Globbing with ?, * and ** is supported.  eg: ["src/\*\*/\*.php", "vendor/\*\*/\*"].  The callback fn() will accept one $file parameter. |
| `watch( array [$srcGlob [, $srcGlob, ...]] )`
|   A string or array of strings that point to source files.  Globbing with ?, * and ** is supported.  eg: ["src/\*\*/\*.php", "vendor/\*\*/\*"].  This will return an object that will emit a "`change`" event whenever a matching file changes. |
| `dest( [$destDir, [$destDir, ...]] )`
|  A string or array of destination directories (not globs).  This is usually the end of a pipeline and does not have a callback function. |
| `task( $name, optional [dependencies], callback fn($p) )`
|  A simple string name to call from command line, optional list of dependent tasks to run before this task starts and a callback function that will receive the `$p` object. |


DataPipe object
===

| method|
| -------- |
|`pipe( WritableStreamInterface $destinationPipe [, array $options] )`
| The pipe method can accept a new DataPipe object or any object that implements WritableStreamInterface.
|`__construct( callback fn($file, $pipe) [, callback fn($pipe) ])`
|The first argument is a callback that is called on every "`data`" event from the source pipe.  The second argument is a callback that is called on the "`end`" event from the source pipe.  Only one callback is required.  Note that the end callback does not receive a $file parameter.
