<?php
namespace Redaxscript;

use cebe\markdown\GithubMarkdown as Markdown;

error_reporting(E_ERROR || E_PARSE);

/* autoload */

include_once('vendor/redaxmedia/redaxscript/includes/Autoloader.php');

/* init */

$autoloader = new Autoloader();
$autoloader->init(
[
	'Redaxscript' => 'vendor/redaxmedia/redaxscript/includes',
	'cebe\markdown' => 'vendor/cebe/markdown',
	'vendor/j4mie/idiorm'
]);

/* get instance */

$config = Config::getInstance();

/* status and config */

$status = 1;
$dbUrl = getenv('DB_URL');
$config->parse($dbUrl);

/* database */

Db::construct($config);
Db::init();

/* sync wiki */

if (Db::getStatus() > 1)
{
	$status = 0;
	$directory = new Directory();
	$directory->init('vendor/redaxmedia/redaxscript.wiki',
	[
		'Home.md',
		'_Sidebar.md'
	]);
	$directoryArray = $directory->getArray();
	$markdown = new Markdown();
	$author = 'wiki-sync';
	$counter = 1000;

	/* delete */

	Db::forTablePrefix('categories')->where('author', $author)->deleteMany();
	Db::forTablePrefix('articles')->where('author', $author)->deleteMany();
	Db::forTablePrefix('categories')
		->create()
		->set(
		[
			'id' => 1000,
			'title' => 'Documentation',
			'alias' => 'documentation',
			'author' => $author
		])
		->save();

	/* process directory */

	foreach ($directoryArray as $value)
	{
		$pathinfo = pathinfo($value);
		if ($pathinfo['extension'] === 'md')
		{
			$title = str_replace('-', ' ', $pathinfo['filename']);
			$alias = strtolower($pathinfo['filename']);
			$content = file_get_contents('vendor/redaxmedia/redaxscript.wiki/' . $value);
			$content = $markdown->parse($content);
			$content = str_replace('<h2>', '<h3 class="rs-title-content-sub">', $content);
			$content = str_replace('</h2>', '</h3>', $content);
			$content = str_replace('<pre>', '<codequote>', $content);
			$content = str_replace('</pre>', '</codequote>', $content);
			$content = str_replace('<blockquote>', '<blockquote class="rs-box-quote">', $content);
			$content = str_replace('<ul>', '<ul class="rs-list-default">', $content);
			$content = str_replace('<ol>', '<ol class="rs-list-default">', $content);
			$content = str_replace('<table>', '<div class="rs-wrapper-table"><table class="rs-table-default">', $content);
			$content = str_replace('</table>', '</table></div>', $content);

			/* create */

			$createStatus = Db::forTablePrefix('articles')
				->create()
				->set(
				[
					'id' => $counter++,
					'title' => $title,
					'alias' => $alias,
					'author' => $author,
					'text' => $content,
					'rank' => $counter,
					'category' => 1000
				])
				->save();

			/* handle status */

			if ($createStatus)
			{
				echo '.';
			}
			else
			{
				$status = 1;
				echo 'F';
			}
		}
	}
	echo PHP_EOL;
}
exit($status);