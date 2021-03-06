<?php
	/**
	 * Base user groups class
	 * @since Version 3.5
	 * @package Railpage 
	 * @author Michael Greenhill
	 */
	 
	// GROUP_OPEN = 0
	// GROUP_CLOSED = 1
	// GROUP_HIDDEN = 2
	
	namespace Railpage\Users; 
	
	/**
	 * Groups class
	 */
	
	class Groups {
		
		/**
		 * Const: open group
		 * @since Version 3.9
		 * @const GROUP_OPEN
		 */
		
		const GROUP_OPEN = 0;
		
		/**
		 * Const: open group
		 * @since Version 3.9
		 * @const GROUP_CLOSED
		 */
		
		const GROUP_CLOSED = 1;
		
		/**
		 * Const: open group
		 * @since Version 3.9
		 * @const GROUP_HIDDEN
		 */
		
		const GROUP_HIDDEN = 2;
		
		/** 
		 * Database object
		 * @since Version 3.5
		 * @var object $db
		 */
		
		public $db; 
		
		/**
		 * Constructor
		 * @since Version 3.5
		 */
		
		public function __construct() {
			require("db/connect.php"); 
			
			$this->db = $db; 
		}
		
		/**
		 * List all groups
		 * @since Version 3.5
		 */
		
		public function getGroups($max_level = 0) {
			$query = "SELECT g.group_id AS id, g.organisation_id, g.group_name AS name, g.group_type AS type, g.group_description AS description, g.group_attrs, g.group_moderator AS owner_user_id, u.username AS owner_username FROM nuke_bbgroups AS g INNER JOIN nuke_users AS u ON g.group_moderator = u.user_id WHERE g.group_single_user = 0 AND g.group_type <= ".$this->db->real_escape_string($max_level)." ORDER BY g.group_name";
			
			if ($rs = $this->db->query($query)) {
				$return = array(); 
				
				while ($row = $rs->fetch_assoc()) {
					$row['group_attrs'] = !empty($row['group_attrs']) ? json_decode($row['group_attrs'], true) : array();
					
					if (filter_var($row['organisation_id'], FILTER_VALIDATE_INT)) {
						$Organisation = new \Railpage\Organisations\Organisation($this->db, $row['organisation_id']); 
						$row['organisation_name'] = $Organisation->name;
					}
					
					ksort($row);
					$return[$row['id']] = $row; 
				}
				
				return $return;
			} else {
				throw new \Exception($this->db->error."\n\n".$query); 
				return false;
			}
		}
		
		/**
		 * Find groups with a specific attribute
		 * @since Version 3.8
		 * @param string $attribute
		 * @param mixed $value
		 * @return array
		 */
		
		public function findWithAttribute($attribute = false, $value = false) {
			if (!$attribute) {
				throw new \Exception("Cannot filter groups by attribute - no attribute given!"); 
				return false;
			}
			
			$groups = $this->getGroups(2); 
			
			foreach ($groups as $id => $group) {
				if (!isset($group['group_attrs'][$attribute])) {
					unset($groups[$id]);
				} elseif ($value && $group['group_attrs'][$attribute] != $value) {
					unset($grouips[$id]);
				}
			}
			
			return $groups;
		}
	}
?>