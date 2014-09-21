<?php
	/**
	 * RailpageCore bootstrapper
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	if (php_sapi_name() == "cli") {
		$_SERVER['SERVER_NAME'] = gethostname(); 
	}
	
	if (!defined("RP_DEBUG")) {
		define("RP_DEBUG", false);
	}
	
	if (!defined("DS")) {
		define("DS", DIRECTORY_SEPARATOR);
	}
	
	/**
	 * Check if PHPUnit is running. Flag it if it is running, so we can set the appropriate DB settings
	 */
	
	if (class_exists("PHPUnit_Framework_TestCase")) {
		$PHPUnitTest = true;
		
		/**
		 * Load the composer autoloader
		 */
		
		if (file_exists(__DIR__ . DS . "vendor" . DS . "autoload.php")) {
			require(__DIR__ . DS . "vendor" . DS . "autoload.php");
		}
	} else {
		$PHPUnitTest = false;
	}
	
	/**
	 * Load the autoloader
	 */
	
	require_once("autoload.php");
	
?>