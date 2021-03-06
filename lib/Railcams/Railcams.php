<?php
	/**
	 * Railcams class
	 * @since Version 3.4
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Railcams; 
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use stdClass;
	
	/**
	 * Railcams base class
	 */
	
	class Railcams extends AppCore {
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			$this->Module = new Module("railcams");
			$this->namespace = $this->Module->namespace;
		}
		
		/**
		 * List all railcams
		 * @since Version 3.4
		 * @return array
		 */
		
		public function listAll() {
			$query = "SELECT * FROM railcams ORDER BY name";
			
			if ($result = $this->db->fetchAll($query)) {
				$return = array(); 
				
				foreach ($result as $row) {
					$return[$row['id']] = $row;
				}
				
				return $return;
			}
		}
		
		/**
		 * Get a Railcam ID from its permalink
		 * @since Version 3.4
		 * @param string $permalink 
		 * @return int|boolean
		 */
		
		public function getIDFromPermalink($permalink = false) {
			if (!$permalink) {
				throw new Exception("Cannot find the railcam ID from the given permalink - no permalink given!"); 
				return false;
			}
			
			$query = "SELECT id FROM railcams WHERE permalink = ?";
			
			if ($id = $this->db->fetchOne($query, $permalink)) {
				return $id;
			} else {
				throw new Exception("Cannot find the railcam ID from the given permalink - no results found"); 
				return false;
			}
		}
		
		/**
		 * Get a Railcam ID from its NSID
		 * @since Version 3.4
		 * @param string $nsid
		 * @return int|boolean
		 */
		
		public function getIDFromNSID($nsid = false) {
			if (!$nsid) {
				throw new Exception("Cannot find the railcam ID from the given Flickr NSID - no NSID given!"); 
				return false;
			}
			
			$query = "SELECT id FROM railcams WHERE nsid = ?";
			
			if ($id = $this->db->fetchOne($query, $nsid)) {
				return $id;
			} else {
				throw new Exception("Cannot find the railcam ID from the given Flickr NSID - no results found"); 
				return false;
			}
		}
		
		/**
		 * Get railcam types
		 * @since Version 3.8
		 * @return array
		 */
		
		public function getTypes() {
			$query = "SELECT * FROM railcams_type ORDER BY name ASC";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[] = $row;
			}
			
			return $return;
		}
	}
?>