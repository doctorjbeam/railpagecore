<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
	
	use Railpage\Module;
	use Exception;
	use DateTime;
	
	/**
	 * Base news class
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	
	class Base extends \Railpage\AppCore {
		
		/**
		 * User handle
		 * @version 3.0
		 * @since Version 3.0
		 * @var object $user
		 */
		
		public $user = false;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			$this->Module = new Module("news");
			$this->namespace = $this->Module->namespace;
		}
		
		/**
		 * Get the latest news items
		 * @version 3.7.5
		 * @since Version 3.0
		 * @return mixed
		 * @param int $number
		 * @param int $offset
		 */
		 
		public function latest($number = 5, $offset = 0) {
			$return = false;
			$mckey = "railpage:news.latest.count=" . $number .".offset=" . $offset;
			$mcexp = strtotime("+5 minutes"); // Store for five minutes
			
			#removeMemcacheObject($mckey);
			
			$Sphinx = $this->getSphinx();
			
			$query = $Sphinx->select("*")
					->from("idx_news_article")
					->orderBy("story_time_unix", "DESC")
					->where("story_active", "=", 1)
					->limit($offset, $number);
					
			$matches = $query->execute(); 
			
			
			if (is_array($matches) && count($matches)) {
				
				foreach ($matches as $id => $row) {
					$row['time_relative'] = time2str($row['story_time_unix']);
					$row['time'] = time2str($row['story_time']);
					
					// Match the first sentence
					$line = explode("\n", str_replace("\r\n", "\n", $row['story_blurb']));
					$row['firstline']	= strip_tags($line[0]);
					
					$row['hometext'] = wpautop(process_bbcode($row['story_blurb']));
					$row['bodytext'] = wpautop(process_bbcode($row['story_body']));
					$row['title'] = format_topictitle($row['story_title']);
					$row['featured_image'] = $row['story_image'];
					
					if (empty($row['slug'])) {
						$row['slug'] = $this->createSlug($row['story_id']); 
					}
					
					$row['url'] = $this->makePermaLink($row['story_slug']); 
					$matches[$id] = $row;
				}
				
				return $matches;
				
			} else {
				if (RP_DEBUG) {
					global $site_debug;
					$debug_timer_start = microtime(true);
				}
				
				if ($data = $this->getCache($mckey)) {
					// Do nothing, it's already been formatted and stored
					
					return $data;
				} else {
					if ($this->db instanceof \sql_db) {
						$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, u.user_id AS informant_id, u.user_id, u.username, u.user_avatar FROM nuke_stories s, nuke_topics t, nuke_users u WHERE u.user_id = s.user_id AND s.topic = t.topicid AND s.approved = 1 ORDER BY s.time DESC LIMIT ".$this->db->real_escape_string($offset).", ".$this->db->real_escape_string($number); 
						
						if ($rs = $this->db->query($query)) {
							$return = array(); 
							
							require_once("includes/functions.php"); 
							
							while ($row = $rs->fetch_assoc()) {
								if (function_exists("relative_date")) {
									$row['time_relative'] = relative_date(strtotime($row['time']));
								} else {
									$row['time_relative'] = $row['time'];
								}
								
								// Match the first sentence
								$line = explode("\n", str_replace("\r\n", "\n", $row['hometext'])); 
								#$row['firstline'] 	= preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
								$row['firstline']	= strip_tags($line[0]);
								
								$row['hometext'] 	= format_post($row['hometext']);
								$row['hometext'] 	= wpautop($row['hometext']);
								
								$return[] = $row; 
							}
							
							$this->setCache($mckey, $return, $mcexp); 
							
							return $return;
						} else {
							throw new \Exception($this->db->error."\n\n".$query);
							return false;
						}
					} else {
						$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, u.user_id AS informant_id, u.user_id, u.username, u.user_avatar 
								FROM nuke_stories AS s
								LEFT JOIN nuke_topics AS t ON s.topic = t.topicid
								LEFT JOIN nuke_users AS u ON s.informant = u.username
								WHERE s.title != \"\"
								AND s.approved = ?
								ORDER BY s.time DESC
								LIMIT ?, ?"; 
						
						if ($result = $this->db_readonly->fetchAll($query, array("1", $offset, $number))) {
							$return = array(); 
							
							foreach ($result as $row) {
								if (function_exists("relative_date")) {
									$row['time_relative'] = relative_date(strtotime($row['time']));
								} else {
									$row['time_relative'] = $row['time'];
								}
								
								// Match the first sentence
								$line = explode("\n", str_replace("\r\n", "\n", $row['hometext']));
								$row['firstline']	= strip_tags($line[0]);
								
								$row['hometext'] 	= format_post($row['hometext']);
								$row['hometext'] 	= wpautop($row['hometext']);
								
								if (empty($row['slug'])) {
									$row['slug'] = $this->createSlug($row['sid']); 
								}
								
								$row['url'] = $this->makePermaLink($row['slug']); 
								
								$return[] = $row; 
							}
							
							$this->setCache($mckey, $return, $mcexp); 
					
							if (RP_DEBUG) {
								$site_debug[] = "Zend_DB: SUCCESS select latest news articles in " . round(microtime(true) - $debug_timer_start, 5) . "s";
							}
							
							return $return;
						}
					}
				}
			}
		}
		
		/**
		 * Add story to database
		 * @version 3.0
		 * @since Version 3.0
		 * @param string $title
		 * @param string $intro
		 * @param string $body
		 * @param string $username
		 * @param int $topic_id
		 * @param string $source
		 * @param float $lat
		 * @param float $lon
		 * @throws \Exception Deprecated - use new \Railpage\News\Article instead
		 * @deprecated Deprecated since Version 3.4
		 * @return mixed
		 */
		
		public function addStory($title, $intro, $body, $username, $topic_id, $source = false, $lat = false, $lon = false) {
			if (!$this->db) {
				return false;
			}
			
			throw new \Exception("Railpage\News\Base::addStory() is deprecated - use Railpage\News\Article instead"); 
			
			$return = false;
			
			$dataArray = array(); 
			
			$dataArray['subject'] 		= $this->db->real_escape_string($title); 
			$dataArray['story'] 		= $this->db->real_escape_string($intro); 
			$dataArray['storyext'] 		= $this->db->real_escape_string($body); 
			$dataArray['uid'] 			= $this->db->real_escape_string($username); 
			$dataArray['timestamp'] 	= "NOW()"; 
			$dataArray['topic'] 		= $this->db->real_escape_string($topic_id); 
			
			if ($lat && $lon) {
				$dataArray['geo_lat']	 	= $this->db->real_escape_string($lat);
				$dataArray['geo_lon']		= $this->db->real_escape_string($lon);
			}
			
			if ($source) {
				$dataArray['source'] = $this->db->real_escape_string($source); 
			}
			
			// Throw it in the pending queue
			$query = $this->db->buildQuery($dataArray, "nuke_queue"); 
			
			if ($rs = $this->db->query($query)) {
				return $this->db->insert_id;
			} else {
				trigger_error("News: could not add story"); 
				trigger_error($this->db->error); 
				trigger_error($query); 
				
				return false;
			}
		}
		
		
		/**
		 * Get pending stories
		 * @version 3.0
		 * @since Version 3.0
		 * @return mixed
		 */
		 
		public function getPending() {
			if (!$this->db) {
				return false;
			}
			
			#$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, u.username FROM nuke_stories AS s, nuke_topics AS t, nuke_users AS u WHERE s.user_id = u.user_id AND s.topic = t.topicid";
			$query = "SELECT s.*, t.topicname, t.topictext, u.username, 'newqueue' AS queue
				FROM nuke_stories AS s
				LEFT JOIN nuke_topics AS t ON s.topic = t.topicid
				LEFT JOIN nuke_users AS u ON s.user_id = u.user_id
				WHERE s.approved = 0
				ORDER BY s.time DESC";
			
			$return = array();
			
			if ($this->db instanceof \sql_db) {
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						if ($row['title'] == "") {
							$row['title'] = "No subject";
						}
						
						$return[] = $row; 
					}
				} else {
					trigger_error("News: unable fetch pending news stories"); 
					trigger_error($this->db->error); 
				}
			} else {
				foreach ($this->db_readonly->fetchAll($query) as $row) {
					if ($row['title'] == "") {
						$row['title'] = "No subject";
					}
					
					$return[] = $row; 
				}
			}
			
			/**
			 * Get stories from the older queue
			 */
			
			$query = "SELECT q.qid AS sid, q.uid AS user_id, u.username, q.subject AS title, q.story AS hometext, q.storyext AS bodytext, q.timestamp AS time, q.source, t.topicname, t.topictext, q.topic, 'oldqueue' AS queue
						FROM nuke_queue AS q
						LEFT JOIN nuke_topics AS t ON q.topic = t.topicid
						LEFT JOIN nuke_users AS u ON q.uid = u.user_id
						ORDER BY q.timestamp DESC";
			
			
			if ($this->db instanceof \sql_db) {
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						if ($row['title'] == "") {
							$row['title'] = "No subject";
						}
						
						$return[] = $row; 
					}
				} else {
					trigger_error("News: unable fetch pending news stories"); 
					trigger_error($this->db->error); 
				}
			} else {
				foreach ($this->db_readonly->fetchAll($query) as $row) {
					if ($row['title'] == "") {
						$row['title'] = "No subject";
					}
					
					$return[] = $row; 
				}
			}
			
						
			
			return $return;
		}
		
		/**
		 * Most read articles this week
		 * @version 3.0
		 * @since Version 3.2
		 * @return mixed
		 * @param int $limit
		 */
		
		public function mostReadThisWeek($limit = 5) {
			$return = false;
			
			if ($this->db instanceof \sql_db) {
				if (isset($this->id) && $this->id > 0) {
					$topic_sql = "AND s.topic = ".$this->db->real_escape_string($this->id);
				} else {
					$topic_sql = NULL;
				}
				
				if ($rs = $this->db->query("SELECT s.*, t.topictext, t.topicname FROM nuke_stories s, nuke_topics t WHERE s.topic = t.topicid ".$topic_sql." AND s.weeklycounter > 0 ORDER BY s.weeklycounter DESC LIMIT 0, ".$this->db->real_escape_string($limit))) {
					$return = array(); 
					
					require_once("includes/functions.php"); 
					
					while ($row = $rs->fetch_assoc()) {
						if (function_exists("relative_date")) {
							$row['time_relative'] = relative_date(strtotime($row['time']));
						} else {
							$row['time_relative'] = $row['time'];
						}
						
						// Match the first sentence
						$line = explode("\n", str_replace("\r\n", "\n", $row['hometext'])); 
						#$row['firstline'] 	= preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
						$row['firstline']	= strip_tags($line[0]);
						
						$return[] = $row; 
					}
				} else {
					trigger_error("News: unable to fetch most read stories for topic id ".$this->id); 
					trigger_error($this->db->error); 
				} 
				
				return $return;
			} else {
				$params = array(); 
				
				if (isset($this->id) && filter_var($this->id, FILTER_VALIDATE_INT)) {
					$topic_sql = "AND s.topic = ?";
					$params[] = $this->id; 
				} else {
					$topic_sql = NULL;
				}
				
				$query = "SELECT s.*, t.topictext, t.topicname FROM nuke_stories s, nuke_topics t WHERE s.topic = t.topicid " . $topic_sql . " AND s.weeklycounter > 0 ORDER BY s.weeklycounter DESC LIMIT 0, ?";
				$params[] = $limit;
				
				if ($result = $this->db_readonly->fetchAll($query, $params)) {
					$return = array(); 
					
					foreach ($result as $row) {
						if (function_exists("relative_date")) {
							$row['time_relative'] = relative_date(strtotime($row['time']));
						} else {
							$row['time_relative'] = $row['time'];
						}
						
						// Match the first sentence
						$line = explode("\n", str_replace("\r\n", "\n", $row['hometext'])); 
						#$row['firstline'] 	= preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
						$row['firstline']	= strip_tags($line[0]);
						
						if (empty($row['slug'])) {
							$row['slug'] = $this->createSlug($row['sid']); 
						}
						
						$row['url'] = $this->makePermaLink($row['slug']); 
						
						$return[] = $row; 
					}
					
					return $return;
				}
			}
		}
		
		/**
		 * List all topics
		 * @version 3.0
		 * @since Version 3.0
		 * @param int $id
		 * @return mixed
		 */
		
		public function topics($id = false) {
			if (!$this->db) {
				throw new \Exception("Cannot fetch news topics - no database connection has been provided to this class");
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$query = "SELECT * FROM nuke_topics WHERE topicid = ".$this->db->real_escape_string($id)." ORDER BY topictext";
				} else {
					$query = "SELECT * FROM nuke_topics ORDER BY topictext";
				}
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$return[] = $row; 
					}
					
					return $return;
				} else {
					throw new \Exception("Could not fetch news topics - " . $e->getMessage()); 
					return false;
				}
			} else {
				$params = array(); 
				$return = array(); 
				
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$query = "SELECT * FROM nuke_topics WHERE topicid = ? ORDER BY topictext";
					$params[] = $id;
				} else {
					$query = "SELECT * FROM nuke_topics ORDER BY topictext";
				}
				
				foreach ($this->db_readonly->fetchAll($query, $params) as $row) {
					$return[] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * Complying with naming conventions
		 * @param int $id
		 */
		 
		public function getTopics($id = false) {
			return $this->topics($id); 
		}
		
		/**
		 * Generate the URL slug for this news article
		 * @since Version 3.7.5
		 * @param int $story_id
		 * @return string
		 */
		
		public function createSlug($story_id = false) {
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
				
			// Assume ZendDB
			$find = array(
				"(",
				")",
				"-",
				"?",
				"!",
				"#",
				"$",
				"%",
				"^",
				"&",
				"*",
				"+",
				"="
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
			if ($story_id) {
				$title = $this->db->fetchOne("SELECT title FROM nuke_stories WHERE sid = ?", $story_id); 
			} elseif (isset($this->title) && !empty($this->title)) {
				$title = $this->title;
				$story_id = $this->id;
			} else {
				return false;
			}
			
			$name = str_replace($find, $replace, $title);
			$proposal = create_slug($name);
			
			/**
			 * Trim it if the slug is too long
			 */
			
			if (strlen($proposal) >= 256) {
				$proposal = substr($poposal, 0, 200); 
			}
			
			/**
			 * Check that we haven't used this slug already
			 */
			
			$result = $this->db_readonly->fetchAll("SELECT sid FROM nuke_stories WHERE slug = ? AND sid != ?", array($proposal, $story_id)); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			if (isset($this->slug)) {
				$this->slug = $proposal;
			}
			
			/**
			 * Add this slug to the database
			 */
			
			$data = array(
				"slug" => $proposal
			);
			
			$where = array(
				"sid = ?" => $story_id
			);
			
			$rs = $this->db->update("nuke_stories", $data, $where); 
			
			if (RP_DEBUG) {
				if ($rs === false) {
					$site_debug[] = "Zend_DB: FAILED create url slug for story ID " . $story_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				} else {
					$site_debug[] = "Zend_DB: SUCCESS create url slug for story ID " . $story_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				}
			}
			
			/**
			 * Return it
			 */
			
			return $proposal;
		}
		
		/**
		 * Make a permalink
		 * @since Version 3.7.5
		 * @return string
		 * @param string|int $entity
		 */
		
		public function makePermaLink($entity = false) {
			if (!$entity) {
				return false;
			}
			
			if (filter_var($entity, FILTER_VALIDATE_INT)) {
				$slug = $this->db_readonly->fetchOne("SELECT slug FROM nuke_stories WHERE sid = ?", $entity); 
				
				if ($slug === false || empty($slug)) {
					$slug = $this->createSlug($entity); 
				}
			} else {
				$slug = $entity;
			}
			
			$permalink = "/news/s/" . $slug; 
			
			return $permalink;
		}
	}
?>