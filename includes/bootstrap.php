<?php
namespace Sync;

use Redaxscript\Autoloader;
use Redaxscript\Config;
use Redaxscript\Db;
use Redaxscript\Language;
use function error_reporting;
use function getenv;

error_reporting(E_ERROR | E_PARSE);

/* include */

include_once('vendor' . DIRECTORY_SEPARATOR . 'redaxscript' . DIRECTORY_SEPARATOR . 'redaxscript' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'Autoloader.php');
include_once('.' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

/* autoload */

$autoloader = new Autoloader();
$autoloader->init(
[
	'Sync' => 'includes' . DIRECTORY_SEPARATOR . 'Sync',
	'Redaxscript' => 'vendor' . DIRECTORY_SEPARATOR . 'redaxscript' . DIRECTORY_SEPARATOR . 'redaxscript' . DIRECTORY_SEPARATOR . 'includes',
	'vendor' . DIRECTORY_SEPARATOR . 'redaxscript' . DIRECTORY_SEPARATOR . 'redaxscript' . DIRECTORY_SEPARATOR . 'libraries'
]);

/* get instance */

$config = Config::getInstance();

/* config */

$dbUrl = getenv('DB_URL');
$config->parse($dbUrl);

/* database */

Db::construct($config);
Db::init();

/* language */

$language = Language::getInstance();
$language->init();
