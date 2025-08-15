<?php

// defined('HOSTCMS') || exit('HostCMS: access denied.');

spl_autoload_register(function ($class) {
	$prefix = 'ZipStream\\';

	if (strpos($class, $prefix) !== 0)
	{
		return '';
	}
	$filename = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';

	$filepath = __DIR__ . DIRECTORY_SEPARATOR . $filename;

	if (!is_readable($filepath))
	{
		return '';
	}

    require($filepath);
});
