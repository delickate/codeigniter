<?php date_default_timezone_set("Asia/Karachi");
//SANI: Making driver class inherting all previous driver features
class MY_DB_mysqli_driver extends CI_DB_mysqli_driver
{
	//SANI: Declairing & initializing some important variables
	private $CI					=	NULL;
	private $LogTableName		=	"tbl_activity_log";			//SANI: Log table name
	private $LogDetailTableName	=	"tbl_activity_log_detail";  //SANI: Log table detail name
	private $configuredsession; 								//SANI: To store session variables configured in application/config/track_config.php file
	private $loggingAction;                                     //SANI: To store which action is to allowed
	private $skipTables;										//SANI: To store which table is not allowed
	
	function __construct($params)
	{
    	parent::__construct($params);
    	//log_message('debug', 'Extended DB driver class instantiated!');
		$this->CI = &get_instance(); 	//SANI: Getting CI instance
		$this->CI->config->load('track_config'); //SANI: loading configured file  application/config/track_config.php file
		$this->configuredsession	=	$this->CI->config->item('activesession'); //SANI: Get session variables, configured in   application/config/track_config.php file
		$this->loggingAction		=	array("INSERT","UPDATE","DELETE","DROP"); //SANI: Action to allow
		$this->skipTables			=	array("tbl_log","usertracking","ci_sessions","ci_session_activity_log","ci_session_log"); //SANI: Table to skip
		
  	}
	
	//SANI: Getting default query features
	function query($sql, $binds = FALSE, $return_object = TRUE, $log = true)
	{	
		if ($sql == '')
		{
			if ($this->db_debug)
			{
				log_message('error', 'Invalid query: '.$sql);
				return $this->display_error('db_invalid_query');
			}
			return FALSE;
		}
		
		//SANI: Check if you want to create a log or not
		if($log)
		{ 
			log_message('debug', 'Sani: Loging Query');
			$this->query_log($sql);
		}else{
				log_message('debug', 'Hyne: Not Loging Query'); 
			 }
		// Verify table prefix and replace if necessary
		if ( ($this->dbprefix != '' AND $this->swap_pre != '') AND ($this->dbprefix != $this->swap_pre) )
		{
			$sql = preg_replace("/(\W)".$this->swap_pre."(\S+?)/", "\\1".$this->dbprefix."\\2", $sql);
		}

		// Compile binds if needed
		if ($binds !== FALSE)
		{
			$sql = $this->compile_binds($sql, $binds);
		}

		// Is query caching enabled?  If the query is a "read type"
		// we will load the caching class and return the previously
		// cached query if it exists
		if ($this->cache_on == TRUE AND stristr($sql, 'SELECT'))
		{
			if ($this->_cache_init())
			{
				$this->load_rdriver();
				if (FALSE !== ($cache = $this->CACHE->read($sql)))
				{
					return $cache;
				}
			}
		}

		// Save the  query for debugging
		if ($this->save_queries == TRUE)
		{
			$this->queries[] = $sql;
		}

		// Start the Query Timer
		$time_start = list($sm, $ss) = explode(' ', microtime());

		// Run the Query
		if (FALSE === ($this->result_id = $this->simple_query($sql)))
		{
			if ($this->save_queries == TRUE)
			{
				$this->query_times[] = 0;
			}

			// This will trigger a rollback if transactions are being used
			$this->_trans_status = FALSE;

			if ($this->db_debug)
			{
				// grab the error number and message now, as we might run some
				// additional queries before displaying the error
				$error_no = $this->_error_number();
				$error_msg = $this->_error_message();

				// We call this function in order to roll-back queries
				// if transactions are enabled.  If we don't call this here
				// the error message will trigger an exit, causing the
				// transactions to remain in limbo.
				$this->trans_complete();

				// Log and display errors
				log_message('error', 'Query error: '.$error_msg);
				return $this->display_error(
										array(
												'Error Number: '.$error_no,
												$error_msg,
												$sql
											)
										);
			}

			return FALSE;
		}

		// Stop and aggregate the query time results
		$time_end = list($em, $es) = explode(' ', microtime());
		$this->benchmark += ($em + $es) - ($sm + $ss);

		if ($this->save_queries == TRUE)
		{
			$this->query_times[] = ($em + $es) - ($sm + $ss);
		}

		// Increment the query counter
		$this->query_count++;

		// Was the query a "write" type?
		// If so we'll simply return true
		if ($this->is_write_type($sql) === TRUE)
		{
			// If caching is enabled we'll auto-cleanup any
			// existing files related to this particular URI
			if ($this->cache_on == TRUE AND $this->cache_autodel == TRUE AND $this->_cache_init())
			{
				$this->CACHE->delete();
			}

			return TRUE;
		}

		// Return TRUE if we don't need to create a result object
		// Currently only the Oracle driver uses this when stored
		// procedures are used
		if ($return_object !== TRUE)
		{
			return TRUE;
		}

		// Load and instantiate the result driver

		$driver			= $this->load_rdriver();
		$RES			= new $driver();
		$RES->conn_id	= $this->conn_id;
		$RES->result_id	= $this->result_id;

		if ($this->dbdriver == 'oci8')
		{
			$RES->stmt_id		= $this->stmt_id;
			$RES->curs_id		= NULL;
			$RES->limit_used	= $this->limit_used;
			$this->stmt_id		= FALSE;
		}

		// oci8 vars must be set before calling this
		$RES->num_rows	= $RES->num_rows();

		// Is query caching enabled?  If so, we'll serialize the
		// result object and save it to a cache file.
		if ($this->cache_on == TRUE AND $this->_cache_init())
		{
			// We'll create a new instance of the result object
			// only without the platform specific driver since
			// we can't use it with cached data (the query result
			// resource ID won't be any good once we've cached the
			// result object, so we'll have to compile the data
			// and save it)
			$CR = new CI_DB_result();
			$CR->num_rows		= $RES->num_rows();
			$CR->result_object	= $RES->result_object();
			$CR->result_array	= $RES->result_array();

			// Reset these since cached objects can not utilize resource IDs.
			$CR->conn_id		= NULL;
			$CR->result_id		= NULL;

			$this->CACHE->write($sql, $CR);
		}
		
		
		//print_r($RES);
		return $RES;
	
			
  	}
	
	function query_log($query)
	{	
		$query			=	str_replace('`', "", $query);
		
		$this->createLogTable(); //SANI: Create log tables into database
		$currentSession = (isset($this->CI->session)?(isset($this->CI->session->userdata)?$this->CI->session->userdata:false):false); //SANI: Get all session variables
		$loginId 		= (isset($currentSession[$this->configuredsession[0]])?(int)$currentSession[$this->configuredsession[0]]:0);  //SANI: Get login id
		$roleId  		= (isset($currentSession[$this->configuredsession[1]])?(int)$currentSession[$this->configuredsession[1]]:0);  //SANI: Get role id if exist
		$class			= $this->CI->router->fetch_class(); //SANI: Get which controller class is being use
		$function		= $this->CI->router->fetch_method(); //SANI: Get which controller function is being use
		$database   	= $this->CI->db->database;  //SANI: Get current Database name
		$action     	= "SELECT";
		$tableName  	= "NULL";
		
		
		$session    	= (isset($currentSession['session_id'])?$currentSession['session_id']:false);
		$postedData     = (($this->CI->input->post())?$this->CI->input->post():NULL);  //SANI: Get posted data if exist
		$action 		= $this->actionName($query);  //SANI: Get which action is going to perform.
		$tableName 		= $this->tableName($query,$action);  //SANI: Get manipulated table name
		$previous   	= $this->previousValue($query,$action); //SANI: Get old values before change in database
		$current    	= "NULL";
		
		if(!in_array($tableName, $this->skipTables)) //SANI: Skip tables
		{
			if (in_array($action, $this->loggingAction)) //SANI: Allow defined actions only
			{
				$execution = "INSERT INTO tbl_activity_log(
																		alog_id, 
																		alog_loginuserid, 
																		alog_roleid,
																		alog_class, 
																		alog_function, 
																		alog_database, 
																		alog_table, 
																		alog_action,
																		alog_level, 
																		alog_datetime,
																		alog_date,
																		alog_session_id
																	) 
															VALUES (NULL, 
																	'".$loginId."', 
																	'".$roleId."', 
																	'".$class."', 
																	'".$function."', 
																	'".$database."', 
																	'".$tableName."', 
																	'".$action."', 
																	'PORTAL',
																	'".date("Y-m-d H:i:s")."',
																	'".date("Y-m-d")."',
																	'".$session."'
																   )";
						
						$this->result_id= $this->simple_query($execution);		
						
						$last_insert_id	= $this->insert_id();
						
						$insertDetail = "INSERT INTO tbl_activity_log_detail(ldt_id, 
																			 ldt_log_idfk, 
																			 ldt_previous, 
																			 ldt_current, 
																			 ldt_query, 
																			 ldt_session, 
																			 ldt_data, 
																			 ldt_date, 
																			 ldt_datetime
																			) 
										 VALUES	(NULL,
										         '".$last_insert_id."',
												 '".$previous."',
												 '".$current."',
												 '".@mysql_real_escape_string($query)."',
												 '".json_encode($currentSession, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)."',
												 '".json_encode($postedData)."',
												 '".date("Y-m-d")."',
												 '".date("Y-m-d H:i:s")."'
										        )";		
					
					$this->simple_query($insertDetail);													   
			}
		}
		
		
	}

	//SANI: Log tables to create
	private function createLogTable()
	{
		try{
    		$query = "CREATE TABLE IF NOT EXISTS ".$this->LogTableName." (
						  alog_id int(11) NOT NULL AUTO_INCREMENT,
						  alog_loginuserid int(11) NOT NULL,
						  alog_roleid int(11) NOT NULL,
						  alog_class varchar(50) NOT NULL,
						  alog_function varchar(50) NOT NULL,
						  alog_database varchar(100) NOT NULL,
						  alog_table varchar(50) NOT NULL,
						  alog_action enum('SELECCT','INSERT','UPDATE','DELETE','DROP') NOT NULL,
						  alog_level enum('PORTAL','DATABASE') NOT NULL,
						  alog_datetime datetime NOT NULL,
						  alog_date date NOT NULL,
						  alog_session_id varchar(50) NOT NULL,
						  PRIMARY KEY (alog_id),
						  KEY alog_loginuserid (alog_loginuserid),
						  KEY alog_date (alog_date)
						) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;			
			         ";
    		$result=$this->simple_query($query);
			
			$query_log = "CREATE TABLE IF NOT EXISTS ".$this->LogDetailTableName." (
						  ldt_id int(11) NOT NULL AUTO_INCREMENT,
						  ldt_log_idfk int(11) NOT NULL,
						  ldt_previous text NOT NULL,
						  ldt_current text NOT NULL,
						  ldt_query text NOT NULL,
						  ldt_session text NOT NULL,
						  ldt_data text NOT NULL,
						  ldt_date date NOT NULL,
						  ldt_datetime datetime NOT NULL,
						  PRIMARY KEY (ldt_id),
						  KEY ldt_log_idfk (ldt_log_idfk),
						  KEY ldt_date (ldt_date)
						) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
						";
			$result=$this->simple_query($query_log);
						
    	} catch (Exception $error)
			{
			}
	}
	
	//SANI: Get string between to strings
	protected function get_string_between($string, $start, $end)
	{
		$string	= strtolower($string);
		$start	= strtolower($start);
		$end	= strtolower($end);
		$string = ' ' . $string;
    	$ini 	= strpos($string, $start);
		
    	if ($ini == 0) return '';
		
    	$ini += strlen($start);
    	$len  = strpos($string, $end, $ini) - $ini;
    	return substr($string, $ini, $len);
	}
	
	//SANI: Get first 6 letters of string
	private function actionName($query)
	{
		return substr($query,0,6);
	}
	
	//SANI: Get table name from query
	private function tableName($query,$currentAction)
	{
		$tableName 	= NULL;
		
			switch($currentAction)
			{ 
				case 'INSERT': $allQuery 	= @mysql_real_escape_string($query); 
							   $tableName 	=  str_replace(' ', '',$this->get_string_between($query, "INSERT INTO", "(")); 
							   break;
				case 'UPDATE': $allQuery 	= @mysql_real_escape_string($query); 
							   $tableName 	=  str_replace(' ', '',$this->get_string_between($query, "UPDATE", "SET"));
							   break;
				case 'DELETE': $allQuery 	= @mysql_real_escape_string($query);
							   $tableName 	=  str_replace(' ', '',$this->get_string_between($query, "DELETE FROM", "WHERE"));
							   break;
				default:	   $allQuery 	= @mysql_real_escape_string($query); break;
							   $tableName 	= NULL;	   
				
			}
		return str_replace('`', "", $tableName);
	}
	
	//SANI: Get old values stored in database
	private function previousValue($query,$currentAction)
	{ 
		$previous 	= NULL;
		
			switch($currentAction)
			{ 
				case 'INSERT': $allQuery 	= @mysql_real_escape_string($query); 
							   $previous 	= "NULL"; 
							   break;
				case 'UPDATE': $allQuery 	= @mysql_real_escape_string($query); 
							   $tableName 	= str_replace(' ', '',$this->get_string_between($query, "UPDATE", "SET"));
							   $str 		= substr($query, strpos($query, 'WHERE'));
							   $previous    = json_encode($this->execution("SELECT * FROM ".$tableName." ".$str));
							   
							  // print_r($result);
							   break;
				case 'DELETE': $allQuery 	= @mysql_real_escape_string($query);
							   $tableName 	=  str_replace(' ', '',$this->get_string_between($query, "DELETE FROM", "WHERE"));
							   $str 		= substr($query, strpos($query, 'WHERE'));
							   $previous    = json_encode($this->execution("SELECT * FROM ".$tableName." ".$str));
							   break;
				default:	   $allQuery 	= @mysql_real_escape_string($query); break;
							   $previous 	= NULL;	   
				
			}
			//echo $previous;
		return $previous;
	}
	
	//SANI: Query to execute
	private function execution($query)
	{
		$driver			= $this->load_rdriver();
		$RES			= new $driver();
		$CR 			= new CI_DB_result();
		
		
		$this->result_id 	= $this->simple_query($query);
		$RES->result_id	 	= $this->result_id;
		//$CR->result_id		= $RES->result_id();
		$CR->num_rows		= $RES->num_rows();
		$CR->result_object	= $RES->result_object();
		$CR->result_array	= $RES->result_array();
		return $CR->result_array;
	}
	
	
	
}
?>