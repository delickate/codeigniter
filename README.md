# codeigniter
This class allows multiple API request simultaneously.

# How to use this class?
<pre>
$obj = new multiapi();
</pre>

It can be use for both GET, POST requests

# For HTTP POST request:

<pre>
//SANI: Request with params only
$obj->data[0]['url'] 	 	     = 'YOUR_URL_ONE';
$obj->data[0]['post'] 	             = array();
$obj->data[0]['post']['param_1']     = 'param_value_1';
$obj->data[0]['post']['param_2']     = 'param_value_2';

//SANI: Request with basic authentication
$obj->data[1]['url'] 	 	             = 'YOUR_URL_TWO';
$obj->data[1]['post'] 		             = array();
$obj->data[1]['post']['auth']['HEADER_KEY']  = 'key_value';
$obj->data[1]['post']['auth']['USERNAME']    = 'username';
$obj->data[1]['post']['auth']['PASSWORD']    = 'password';
$obj->data[1]['post']['auth']['param_1']     = 'param_value_1';

//SANI: Request with params and header key
$obj->data[3]['url'] 	 	     	     = 'YOUR_URL_ONE';
$obj->data[3]['post'] 	             	     = array();
$obj->data[3]['post']['auth']['HEADER_KEY']  = 'key_value';
$obj->data[3]['post']['param_1']     	     = 'param_value_1';

//SANI: POST DATA	
$result = $obj->post_process_requests();
 print_r($result);
</pre>



# For HTTP GET request:
<pre>
$obj->data = array(
					  'YOUR_URL_1',
					  'YOUR_URL_2',
					  'YOUR_URL_3',
					);
//SANI: GET DATA	
$result = $obj->get_process_requests();
print_r($result);
</pre>

If you have any suggestion please do let me know.
