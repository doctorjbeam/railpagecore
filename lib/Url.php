<?php
	/**
	 * URL class
	 * Provide links to various aspects (SELF, UPDATE, whatever) while retaining a __toString() function for older code
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\fwlink;
	
	/**
	 * URLs
	 */
	
	class Url {
		
		/**
		 * Default URL
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param string $default_url
		 */
		
		public function __construct($default_url = false) {
			
			if ($default_url !== false) {
				
				$this->url = $default_url;
				
				$fwlink = new fwlink($this->url);
				$this->short = $fwlink->url_short;
				
			}
			
		}
		
		/**
		 * Return the default URL
		 * @return string
		 */
		
		public function __toString() {
			
			return $this->url;
			
		}
		
		/**
		 * Get the list of URLs as an associative array
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getURLs() {
			
			return get_object_vars($this);
			
		}
	}
?>