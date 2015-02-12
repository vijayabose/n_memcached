<?php
	
	require_once '..Library/cache/N_Memcached.php';

	$config = array();
	//Unique App ID for the application  
	$config['application']['resource']['appId'] = 'APP_ID';
	$config['resource']['cache']['appId'] = 'APP_ID';
	$config['resource']['cache']['memcached']['enabled'] = 1;
	$config['resource']['cache']['memcached']['expiration'] = '4500';
	$config['resource']['cache']['memcached']['server']['params']['host'] = '127.0.0.1';
	$config['resource']['cache']['memcached']['server']['params']['port'] = '11211';
	$config['resource']['cache']['memcached']['persistent']['ID'] = 'APP_ID_PERSISTENT';


	if(isset($config['resource']['cache']['memcached']['enabled']) && $config['resource']['cache']['memcached']['enabled'] == true){
	     try{  
	        $cache = new N_Memcached($config);
			if($cache){  
				//Set cache object in Registry  
			 	$cache->set('MyKey1','My Vlaue1');  
			 	$cache->set('MyKey2','My Vlaue2');  
			 	echo  "My Key1 is :";  
			 	print_r($cache->get('MyKey1'));  
			 	echo "<br>";  
			 	echo  "My Key2 is :" ;  
			 	print_r($cache->get('MyKey2'));  
			    echo "<br>";  
			}  	        
	     }catch (Exception $ex){  
	          echo $ex->getMessage();
	          die();
	     }
	}


