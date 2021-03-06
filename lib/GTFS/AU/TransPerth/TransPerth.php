<?php
	/**
	 * Transport for WA GTFS interface
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\TransPerth;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Select;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\GTFSInterface;
	
	/**
	 * TransPerth class
	 */
	
	class TransPerth implements GTFSInterface {
		
		/**
		 * Timetable data source
		 * @var string $provider
		 */
		
		public $provider = "TransPerth";
		
		/**
		 * Adapter object
		 * @var object $adapter
		 */
		
		public $adapter;
		
		/**
		 * Database object
		 * @var object $db
		 */
		
		public $db;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			
			if (function_exists("getRailpageConfig")) {
				$this->Config = getRailpageConfig();
			}
			
			$this->adapter = new Adapter(array(
				"driver" => "Mysqli",
				"database" => $this->Config->GTFS->PTV->db_name,
				"username" => $this->Config->GTFS->PTV->db_user,
				"password" => $this->Config->GTFS->PTV->db_pass,
				"host" => $this->Config->GTFS->PTV->db_host
			));
			
			$this->db = new Sql($this->adapter);
		}
		
		/**
		 * Fetch
		 * @param string $method
		 * @param string $parameters
		 * @param string $other
		 * @return string
		 */
		
		public function fetch($method, $parameters, $other) {
			return "Not implemented";
		}
		
		/**
		 * Health
		 * @return string
		 */
		
		public function Health() {
			return "Not implemented";
		}
		
		/**
		 * Get stops near a location
		 * @param double $latitude
		 * @param double $longitude
		 * @return array
		 */
		
		public function StopsNearLocation($latitude = false, $longitude = false) {
			if (!$latitude) {
				throw new Exception("Cannot fetch " . __METHOD__ . " - no latitude given");
			}
			
			if (!$longitude) {
				throw new Exception("Cannot fetch " . __METHOD__ . " - no longitude given");
			}
			
			$query = "SELECT
						stop_id,
						stop_name,
						stop_lat,
						stop_lon,
						wheelchair_boarding, (
							  3959 * acos (
							  cos ( radians(" . $latitude . ") )
							  * cos( radians( stop_lat ) )
							  * cos( radians( stop_lon ) - radians(" . $longitude . ") )
							  + sin ( radians(" . $latitude . ") )
							  * sin( radians( stop_lat ) )
							)
						) AS distance
						FROM au_wa_stops
						WHERE location_type = 1
						HAVING distance < 3
						ORDER BY distance
						LIMIT 0 , 50";
			
			$result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE); 
			
			$return = array();
			
			foreach ($result as $row) {
				$row = $row->getArrayCopy();
				$row['provider'] = $this->provider;
				$row['distance'] = vincentyGreatCircleDistance($row['stop_lat'], $row['stop_lon'], $latitude, $longitude);

				
				$return[] = $row;
			}
			
			return $return;
		}
	}
?>