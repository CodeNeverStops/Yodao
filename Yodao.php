<?php
spl_autoload_register(function ($className) {
	$fileName = '';
	$namespace = '';

	$basePath = dirname(__FILE__);

	if (false !== ($lastNsPos = strripos($className, '\\'))) {
		$namespace = substr($className, 0, $lastNsPos);
		$className = substr($className, $lastNsPos + 1);
		$fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}
	$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	$fullFileName = $basePath . DIRECTORY_SEPARATOR . $fileName;

	if (file_exists($fullFileName)) {
		require $fullFileName;
	} else {
		echo 'Class "'.$className.'" does not exist.';
	}
}); 
