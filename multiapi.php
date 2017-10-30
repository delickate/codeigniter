<?php
//SANI: Class for multi request API
class multiapi
{
	//SANI: Declairing variables
	public $data;
	
	//SANI: Constructor
	function __construct() 
	{
       $this->data = array(); //SANI: Initializing variable
	}
	
	
	public function post_process_requests()
	{
		//SANI: POST
		$response = $this->multiple_post_requests($this->data);
		return $response;
	}
	
	public function get_process_requests()
	{
		//SANI: GET
		$response = $this->multiple_get_requests($this->data);
		return $response;
	}

   
    //SANI: Curl to process multiple POST requests
	function multiple_post_requests($data, $options = array()) 
	{
		 $curly 	= array();
		 $result 	= array();
		 $mh 		= curl_multi_init();
		 //echo "<pre>"; print_r($data); die();
		 foreach ($data as $id => $d) 
		 {
		 	
			$curly[$id] = curl_init();
		 	$url 		= (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
			
			curl_setopt($curly[$id], CURLOPT_URL,            $url);
			curl_setopt($curly[$id], CURLOPT_HEADER,         0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curly[$id], CURLOPT_TIMEOUT, 4);
			curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, 4);
		 
			if (is_array($d)) 
			{ 
			  if (!empty($d['post'])) 
			  { 
			  	curl_setopt($curly[$id], CURLOPT_POST,       1);
				
			  	if(!empty($d['post']['auth']))
				{
					$headers  = $d['post']['auth']["headers"];
					$username = $d['post']['auth']["username"];
					$password = $d['post']['auth']["password"];
					$string   = $d['post']['auth']["string"];
					
					if(!empty($d['post']['auth']["headers"]))
					{
						curl_setopt($curly[$id], CURLOPT_HTTPHEADER, $headers);
					}
					
					if(!empty($d['post']['auth']["username"]) && !empty($d['post']['auth']["password"]))
					{
						curl_setopt($curly[$id], CURLOPT_USERPWD, "$username:$password");
					}
					curl_setopt($curly[$id], CURLOPT_POSTFIELDS,    $string); 
				}else{
						curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
					 }		
			  }
			}
			
			
		 
			if (!empty($options)) {  curl_setopt_array($curly[$id], $options); }
		    curl_multi_add_handle($mh, $curly[$id]);
		  }
		 
		  $running = null;
		  do { curl_multi_exec($mh, $running);  } while($running > 0);
		  foreach($curly as $id => $c) {	$result[$id] = curl_multi_getcontent($c); curl_multi_remove_handle($mh, $c);  }
		  curl_multi_close($mh);
		 
		  return $result;
	}
	
	
	 //SANI: Curl to process multiple GET requests
	function multiple_get_requests($data, $options = array()) 
	{
 
	  $curly 	= array();
	  $result 	= array();
	  $mh 		= curl_multi_init();
	 
	  foreach ($data as $id => $d)
	  {
	 
		$curly[$id] = curl_init();
	 
		$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
		curl_setopt($curly[$id], CURLOPT_URL,            $url);
		curl_setopt($curly[$id], CURLOPT_HEADER,         0);
		curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
	 
		if (is_array($d)) 
		{
		  if (!empty($d['post'])) 
		  {
			curl_setopt($curly[$id], CURLOPT_POST,       1);
			curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
		  }
		}
	 
		if (!empty($options)) {  curl_setopt_array($curly[$id], $options);}
	 
		curl_multi_add_handle($mh, $curly[$id]);
	  }
	 
	  $running = null;
	  do {
			curl_multi_exec($mh, $running);
		  } while($running > 0);
	 
	  foreach($curly as $id => $c) 
	  {
		$result[$id] = curl_multi_getcontent($c);
		curl_multi_remove_handle($mh, $c);
	  }
	 
	  curl_multi_close($mh);
	 
	  return $result;
	}
	 
	
	//SANI: Disctructor
	function __destruct() 
	{
       
    }
	
}

$obj = new multiapi();

///////////////////////////// POST  ////////////////////////////////////
/*
//SANI: request one
$obj->data[0]['url'] 	 		  = 'https://www.xxx.com/xxxx';
$obj->data[0]['post'] 			  = array();
$obj->data[0]['post']['sec_key']  = 'xxxxxxx';
$obj->data[0]['post']['xxxx']     = 'xxxxxxxxxx';

//SANI: request two
$obj->data[1]['url'] 	 		  		  = 'http://wwww.xxxxx.com/xxx';
$obj->data[1]['post'] 			  		  = array();
$obj->data[1]['post']['sec_key']   		  = 'xxxxxxx';
$obj->data[1]['post']['xxxx']  	  		  = 'xxxxx';
$obj->data[1]['post']['xxxxxxxx']	  	  = 'xxxxxxx';

$result = $obj->post_process_requests();
echo "<pre>"; print_r($result);
*/

///////////////////////////// GET  ////////////////////////////////////

//SANI: GET DATA
//$obj->data = array(
//					  'http://wwww.xxxxx.com/xxxxx',
//					  'http://wwww.xxxxx.com/xxxxx',
//					  'http://wwww.xxxxx.com/xxxxx',
//					);
////SANI: GET DATA	
//$result = $obj->get_process_requests();
//echo "<pre>"; print_r($result);
//////////////////////////////////////////////////////////////////////////////////
echo "Initialize request and then process.";
	


?>
