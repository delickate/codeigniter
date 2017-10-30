# codeigniter
This class allow to multiple API request simultaneously.

# How to use this class?
$obj = new multiapi();

It can be use for both GET, POST requests

# For HTTP POST request:

//SANI: request one
$obj->data[0]['url'] 	 		        = 'https://www.xxx.com/xxxx';
$obj->data[0]['post'] 			      = array();
$obj->data[0]['post']['sec_key']  = 'xxxxxxx';
$obj->data[0]['post']['xxxx']     = 'xxxxxxxxxx';

//SANI: request two
$obj->data[1]['url'] 	 		  		        = 'http://wwww.xxxxx.com/xxx';
$obj->data[1]['post'] 			  		      = array();
$obj->data[1]['post']['sec_key']   		  = 'xxxxxxx';
$obj->data[1]['post']['xxxx']  	  		  = 'xxxxx';
$obj->data[1]['post']['xxxxxxxx']	  	  = 'xxxxxxx';

//SANI: POST DATA	
$result = $obj->post_process_requests();
echo "<pre>"; print_r($result);




# For HTTP GET request:
$obj->data = array(
					  'http://wwww.xxxxx.com/xxxxx',
					  'http://wwww.xxxxx.com/xxxxx',
					  'http://wwww.xxxxx.com/xxxxx',
					);
//SANI: GET DATA	
$result = $obj->get_process_requests();
echo "<pre>"; print_r($result);
