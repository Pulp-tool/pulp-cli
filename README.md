Install
===
Download the phar file, make it executable and move it to /usr/local/bin/pulp
```bash
wget https://github.com/Pulp-tool/pulp-cli/releases/download/0.5.2/pulp.phar
chmod a+x pulp.phar
mv pulp.phar /usr/local/bin
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
			->pipe(new \Pulp\DataPipe(function($data) {
				echo "Data Pipe got "; var_dump($data);
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
