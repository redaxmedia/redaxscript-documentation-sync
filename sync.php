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

if (Db::getStatus() === 2)
{
	$status = 0;
	$markdown = new Markdown();
	$directory = new Directory();
	$directory->init('vendor/redaxmedia/redaxscript.wiki',
	[
		'Home.md',
		'_Sidebar.md'
	]);
	$directoryArray = $directory->getArray();
	$syncArray = json_decode(file_get_contents('sync.json'), true);
	$author = 'wiki-sync';
	$categoryId = 1000;
	$articleId = 1000;

	/* delete */

	Db::forTablePrefix('categories')->whereIdIs($categoryId)->deleteMany();
	Db::forTablePrefix('articles')->where('category', $categoryId)->deleteMany();
	Db::forTablePrefix('categories')
		->create()
		->set(
		[
			'id' => $categoryId,
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
			$content = str_replace($syncArray['search'], $syncArray['replace'], $content);

			/* create */

			$createStatus = Db::forTablePrefix('articles')
				->create()
				->set(
				[
					'id' => $articleId++,
					'title' => $title,
					'alias' => $alias,
					'author' => $author,
					'text' => $content,
					'rank' => $articleId,
					'category' => $categoryId
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

	/* auto increment */

	Db::rawInstance()->rawExecute('ALTER TABLE categories AUTO_INCREMENT = 2000');
	Db::rawInstance()->rawExecute('ALTER TABLE articles AUTO_INCREMENT = 2000');
}
exit($status);