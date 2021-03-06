<?php
	/**
	 * Locations mananagement class for jobs module
	 * @since Version 3.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Jobs; 
	
	use Exception;
	use DateTime;
	
	/**
	 * Locations class
	 */
	
	class Locations extends Jobs {
		
		/**
		 * Return a list of child locations
		 * @param int $parent_id
		 * @return array
		 */
		
		public function getChildLocations($parent_id = 0) {
			
			$query = "SELECT jn_location_id, jn_location_name, ? AS jn_parent_id FROM jn_locations WHERE jn_parent_id = ? ORDER BY jn_location_name"; 
			
			if ($this->db instanceof \sql_db) {
				if ($stmt = $this->db->prepare($query)) {
					$stmt->bind_param("ii", $parent_id, $parent_id); 
					
					$stmt->execute(); 
					
					$stmt->bind_result($jn_location_id, $jn_location_name); 
					
					$return = array(); 
					
					while ($stmt->fetch()) {
						$row = array(); 
						
						$row['jn_location_name'] 	= $jn_location_name;
						$row['jn_parent_id'] 		= $parent_id;
						$row['jn_location_id']		= $jn_location_id; 
						
						$return[] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error."\n\n".$query);
					return false;
				}
			} else {
				return $this->db->fetchAll($query, array($parent_id, $parent_id)); 
			}
		}
	}
?>