# N_Memcached
Mecached is a very powerful cacheing server. Which is used for caching backend data in the application. Which will reduce application database load and increase execution speed. Memcached will store data in key-value format. This simple key value stucture is the power of this cache. But this will limit the application in different ways. If you are having lots of combined application running in your servers and want to cache across the applications it will be a hard task.

#Purpose
1. Use same memecahed for multiple application.
2. Use memcached for frameworks with out getting affected by applications.

#Basic setup:

Memcached : http://memcached.org
PHP Setup : http://tugdualgrall.blogspot.com/2011/11/installing-memcached-on-mac-os-x-and.html
[Please refer other docs for other oprating systems]

# What is new?
N in the "N_Memcached" stands for "Namespace". Using N_Memcached we can crate namespaced keys for any entry in the Memcached servers. Evern we can reserve a special namespace for Framework caching.You can use framework namespace for cacheing a common piece of information shared by all applications.

#Basic usage:

<?php
	
	require_once 'N_Memcached.php';

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
			 	$cache->set('Key1','Vlaue1');  
			 	$cache->set('Key2','Vlaue2');  
			 	echo  "Key1 is :";  
			 	print_r($cache->get('Key1'));  
			 	echo "<br>";  
			 	echo  "Key2 is :" ;  
			 	print_r($cache->get('Key2'));  
			    echo "<br>";  
			}  	        
	     }catch (Exception $ex){  
	          echo $ex->getMessage();
	          die();
	     }
	}
	


#Property List



 Following are the list of properties supported by Memcached class:



1.__constructor({config} $config,{string} $appld) - Create  memcached instance.
                        $config : Array, with all memcahed global settings
                        $appId : String, If not specified check application.ini else default to 'DEFAULT'. Data store under 'DEFAULT' namespace can be cleared by any other application.

2. enable() - Boolean flag to enable Memcached for current App
3. disable() - Boolean flag to disable Memcached for current App
4. isEnabled() - Check memcached is enabled for current App
5. enablePersistentConnection - Boolean flag to enable Memcached to have persistent connection
6. disablePersistentConnection - Boolean flag to dibble Memcached to have persistent connection
7. isPersistentConnectionEnabled - Boolean flag to show persistent connection status 
8. getExpirationTime() - Get memcahed expiration time
9. setExpirationTime({integer} $time) - Integer , Set expiration time for memcached, default value is '3600'
10. enableFallOver() - Boolean flag for enabling fail over option for clustered servers
11. disableFallOver() - Boolean flag for disabling fail over option for clustered servers
12. isFallOverEnabled() - Check if fail over option is enabled.
13. set({string} $key, {string} $value, {string} $expires) : Set memcached value.
                    $key : String, Memcache Key
                    $value : String, Memcache Value
                    $expires : String, Expiration time for memcached value [Optional]
14. get({string} $key) : Get value for a key.
                    $key : String, Memcache Key
15. delete({string} $key) : Delete and existing key from Memcached
                    $key : String, Memcache Key
16. deleteApp() : Delete all Keys associated with current App	
