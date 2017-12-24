#pulp

pulp is gulp in PHP.

```php
$p = new \Pulp\Pulp();

$p->task('build', ['log'], function() {
	echo "Building...\n";
});

$foo = time();

$p->task('log', function() use ($foo) {
	echo "Logging with variable: $foo...\n";
});

$p->task('watch', function() use($p) {
	$p->watch( ['src/**/*.php', 'foo/**/*.php'])->on('change', function($file) use ($p) {
		$p->src(['src/', 'foo/'])
			->pipe(new \Pulp\DataPipe(function($data) {
				echo "Data Pipe got "; var_dump($data);
			})
		);
			
		echo "File changed: ".$file." ...\n";
	});
});


$p->exec('build');
$p->exec('watch');
```
