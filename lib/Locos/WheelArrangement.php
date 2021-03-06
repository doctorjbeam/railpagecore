<?php
	/**
	 * Locomotive wheel arrangement object
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Exception;
	
	/**
	 * Locomotive wheel arrangement object
	 * @since Version 3.8.7
	 */
	
	class WheelArrangement extends Locos {
		
		/**
		 * Wheel arrangement ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Wheel arrangement name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Wheel arrangement
		 * @since Version 3.8.7
		 * @var string $arrangement
		 */
		
		public $arrangement;
		
		/**
		 * URL Slug
		 * @since Version 3.8.7
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @var int|string $id
		 */
		
		public function __construct($id = NULL) {
			parent::__construct();
			
			if (!is_null($id)) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$row = $this->db->fetchRow("SELECT * FROM wheel_arrangements WHERE id = ?", $id);
				} elseif (is_string($id)) {
					$row = $this->db->fetchRow("SELECT * FROM wheel_arrangements WHERE slug = ?", $id);
				}
				
				if (isset($row) && count($row)) {
					$this->id = $row['id']; 
					$this->name = $row['title'];
					$this->arrangement = $row['arrangement'];
					$this->slug = $row['slug'];
					
					if (empty($this->slug)) {
						$proposal = create_slug($this->arrangement);
						$proposal = substr($proposal, 0, 30);
						
						$query = "SELECT id FROM wheel_arrangements WHERE slug = ?";
						$result = $this->db->fetchAll($query, $proposal);
						
						if (count($result)) {
							$proposal = $proposal . count($result);
						}
						
						$this->slug = $proposal;
						$this->commit();
					}
					
					$this->url = sprintf("/locos/wheelset/%s", $this->slug);
				}
			}
		}
		
		/**
		 * Validate changes to this wheelset 
		 * @since Version 3.8.7
		 * @return true
		 * @throws \Exception if $this->arrangement is empty
		 */
		
		public function validate() {
			if (empty($this->arrangement)) {
				throw new Exception("Cannot validate changes to this wheel arrangement: arrangement cannot be empty");
				return false;
			}
					
			if (empty($this->slug)) {
				$proposal = create_slug($this->arrangement);
				$proposal = substr($proposal, 0, 30);
				
				$query = "SELECT id FROM wheel_arrangements WHERE slug = ?";
				$result = $this->db->fetchAll($query, $proposal);
				
				if (count($result)) {
					$proposal = $proposal . count($result);
				}
				
				$this->slug = $proposal;
				$this->url = sprintf("/locos/wheelset/%s", $this->slug);
			}
			
			return true;
		}
		
		/**
		 * Save changes to this wheelset
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			$this->validate();
			
			$data = array(
				"title" => $this->name,
				"arrangement" => $this->arrangement,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("wheel_arrangements", $data, $where);
			} else {
				$this->db->insert("wheel_arrangements", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $this;
		}
		
		/**
		 * Get locomotive classes built by this wheel arrangement
		 * @return array
		 */
		
		public function getClasses() {
			$query = "SELECT id, name, loco_type_id, introduced AS year_introduced, manufacturer_id, wheel_arrangement_id FROM loco_class WHERE wheel_arrangement_id = ? ORDER BY name";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$LocoClass = new LocoClass($row['id']);
				$Manufacturer = new Manufacturer($row['manufacturer_id']);
				$LocoType = new Type($row['loco_type_id']);
				
				$row['url'] = $LocoClass->url;
				$row['manufacturer'] = $Manufacturer->name;
				$row['manufacturer_url'] = $Manufacturer->url;
				$row['loco_type'] = $LocoType->name;
				$row['loco_type_url'] = $LocoType->url;
				$row['year_introduced_url'] = $this->makeYearURL($row['year_introduced']);
				
				$return[] = $row;
			}
			
			return $return;
		}
	}
?>