<?php
namespace Redaxscript;

use cebe\markdown\GithubMarkdown as Markdown;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

error_reporting(E_ERROR || E_PARSE);

/* autoload */

include_once('vendor/redaxmedia/redaxscript/includes/Autoloader.php');

/* init */

$autoloader = new Autoloader();
$autoloader->init(
[
	'Redaxscript' => 'vendor/redaxmedia/redaxscript/includes',
	'cebe\markdown' => 'vendor/cebe/markdown',
	'vendor/redaxmedia/redaxscript/libraries'
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

/* sync documentation */

if (Db::getStatus() === 2)
{
	$status = 0;
	$reader = new Reader();
	$markdown = new Markdown();
	$directory = new RecursiveDirectoryIterator('vendor/redaxmedia/redaxscript-documentation/documentation', RecursiveDirectoryIterator::SKIP_DOTS);
	$directoryIterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
	$tidyArray = $reader->loadJSON('tidy.json', true)->getArray();
	$author = 'documentation-sync';
	$categoryId = 1000;
	$articleId = 1000;

	/* delete */

	Db::forTablePrefix('categories')->where('author', 'documentation-sync')->deleteMany();
	Db::forTablePrefix('articles')->where('author', 'documentation-sync')->deleteMany();
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

	foreach ($directoryIterator as $key => $value)
	{
		$basenameArray = explode('.', $value->getBasename());
		$title = ucwords(str_replace('-', ' ', $basenameArray[1]));
		$alias = $basenameArray[1];
		$rank = intval($basenameArray[0]);

		/* create category */

		if ($value->isDir())
		{
			$createStatus = Db::forTablePrefix('categories')
				->create()
				->set(
				[
					'id' => ++$categoryId,
					'title' => $title,
					'alias' => $alias,
					'author' => $author,
					'rank' => $rank,
					'parent' => 1000
				])
				->save();
		}

		/* else create article */

		else
		{
			$directoryParent = trim(strrchr(dirname($value->getPathname()), '/'), '/');
			$href = str_replace('vendor/redaxmedia/redaxscript-documentation', 'https://github.com/redaxmedia/redaxscript-documentation/edit/master', $value->getPathname());
			$content = file_get_contents($value->getPathname());
			$content = $markdown->parse($content);
			$content = str_replace($tidyArray['search'], $tidyArray['replace'], $content);
			$content .= '<a href="' . $href . '" class="rs-link-documentation">Edit on GitHub</a>';
			$createStatus = Db::forTablePrefix('articles')
				->create()
				->set(
				[
					'id' => $articleId++,
					'title' => $title,
					'alias' => $alias,
					'author' => $author,
					'text' => $content,
					'rank' => $rank,
					'category' => $directoryParent === 'documentation' ? 1000 : $categoryId
				])
				->save();
		}

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
	echo PHP_EOL;

	/* auto increment */

	Db::rawInstance()->rawExecute('ALTER TABLE categories AUTO_INCREMENT = 3000');
	Db::rawInstance()->rawExecute('ALTER TABLE articles AUTO_INCREMENT = 3000');
}
exit($status);