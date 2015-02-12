<?php
/**
 * Memcached library
 *
 * File contain the basic wrapper for memcahed php modules
 *
 * @category   Library
 * @package    Cache
 * @subpackage Cache
 * @version    0.1
 */
/** 
 * 
 * Extend memcahed class and wrap methods for injecting
 * name space and impose default expiration time.
 * 
 * 
 * @package  library
 * @since   0.1
 * @date Aug 28, 2012
 */
class N_Memcached extends Memcached
{ 
    /**
     * Default expiration time for cached 
     * data. You can override this from application layer
     * @todo: Currently not enforcing
     * @var {string}
     */
    const DEFAULT_EXPIRE_TIME = '3600';
    
    /**
     * Highly recommend not to use this AppId
     * for any other application. In any case
     * we need to cache any other framework components
     * then use this key for writing into same memcahe
     * location
     * @var {string}
     */
    const FRAMEWORK_DEFAULT_APP_ID = 'FRAMEWORK_DEFAULT_CACHE_CONSTANT';
    
    /**
     * Default AppID is used which is override by
     * all other application with their appId for app
     * specific memory allocation
     * @var {string}
     */
    const DEFAULT_APP_ID = 'DEFAULT';
    
	/**
     * Default persistent connection ID
     * @var {string}
     */
		const DEFAULT_MEMCACHED_PERSISTENT_ID = 'DEFAULT_MEMCACHED_PERSISTENT_ID';
    
    /**
     * Group all cached data  by framework
     * with following name space
     * @var {string}
     */
    private $_namespaceFrameWork = 'FRAMEWORK';
    
    /**
     * Store memcache server information
     * @access private
     * @var {array}
     */
    private $_config;
    
    /**
     * Store memcache data expiration time
     * @access private
     * @var {string}
     */
    private $_expirationTime;
    
		/**
     * Store connection status
     * 
     * @access private
     * @var {Boolean}
     */
    private $_connected = FALSE;
    
    /**
     * Store cache status as enabled or
     * disabled. Default memcahed is enabled
     * 
     * @access private
     * @var {Boolean}
     */
    private static $_isEnabled = TRUE;
		
		/**
     * Store persistent connection is enabled. By default
		 * all connections are persistent
     * 
     * @access private
     * @var {Boolean}
     */
    private static $_isPersistentEnabled = TRUE;
		
    /**
     * If fail over is enabled then after connection
     * failure to any Memcached server, error will
     * be recorded in the stack and try for next
     * server in the stack. Else it will throw 
     * the exception and stop connecting the following
     * list of servers
     *
     * @access private
     * @var {Boolean}
     */
    private $_isFalloverEnabled = TRUE;
    /**
     * Store application ID.To write and read any data
     * into a Memcached server we need an AppId, all data
     * will be written under respective AppId Hash in the 
     * memcached server.
     *
     * @access private
     * @var {string}
     */
    private $_appId;
    
    /**
     * Set compression while setting cache data
     * 
     * @todo: Currently not enforcing
     * 
     * @access private
     * @var {string}
     */
    private $_compression = 0;
    /**
     * Store version for a name space
     * 
     * @access private
     * @var {string}
     */
    private $_version;
    
    /**
     * Store name space.
     * 
     * Name space format:
     * 
     * FRAMEWORK:APP_ID
     *
     * @access private
     * @var {string}
     */
    private $_namespace;
    
	/**
     * Store persistent connection id
     * 
	 * Default to DEFAULT_MEMCACHED_PERSISTENT_CONNECTION
	 * 
     * @access private
     * @var {string}
     */
    private $_persistentConnectionId;
    
    /**
     * 
     * Memcache server configuration
     * 
     * Array format single sever:
     * ['server']
     *    ['host'] => '127.0.0.1'
     *    ['port'] => '11211'
     * 
     * Multile server:
     * 
     * ['server1']
     *    ['host'] => '127.0.0.1'
     *    ['port'] => '11211'   
     * ['serve2']
     *    ['host'] => '127.0.0.1'
     *    ['port'] => '11211'   
     * 
     * 
     * All framework objects will be having a common appId
     * this ID should not be used by any other application
     * 
     * 
     * Cache should be enabled for caching any data in the server
     * 
     * In this class we are only overriding get() and set() for namespace,
     * while using all other crude operations use getNamespaceKey for actual
     * key in the memcached server
     * 
     * @access public
     * @param {array} $config, Memcache configuration
     * @param {string} $appId, UniqueId for Applications
     * @return {N_Memcached}
     */
    public function __construct($config = NULL,$appId = NULL){				
        //If memcached is not enabled then it will return FALSE[Off Memcache in App level]
        if(!self::$_isEnabled){
            return false;
        }
		//Check memcahe module is enabled in php
		if(!extension_loaded ('Memcached')){
			throw Exception("Memcached is disabled, you need to enable it before instantiation");
		}
        $this->_config = $config;
		if (!isset($this->_config['resource']['cache']['memcached']['server'])) {
            throw new Exception('Empty memcached server configuration');
        }
		$appId = isset($this->_config['application']['resource']['appId'])? $this->_config['application']['resource']['appId'] : NULL; 
        //Check AppId exist for writing or reading data
        $this->_appId = isset($appId)? $appId : self::DEFAULT_APP_ID;
		//Check expiration time
		if(isset($this->_config['resource']['cache']['memcached']['expiration'])){
		  $this->_expirationTime = $this->_config['resource']['cache']['memcached']['expiration'];
		}else{
		  $this->_expirationTime = self::DEFAULT_EXPIRE_TIME;
		}
		if(self::$_isPersistentEnabled == false){
				$this->_persistentConnectionId = null;
		 }else{
		  if(isset($this->_config['resource']['cache']['memcached']['persistent']['ID'])){
				$this->_persistentConnectionId = $this->_config['resource']['cache']['memcached']['persistent']['ID'];
		 }else{
				$this->_persistentConnectionId = self::DEFAULT_MEMCACHED_PERSISTENT_ID;
		 }		
		}
		//@todo Will be used for call back
		$callback = null;
		call_user_func('parent::__construct',$this->_persistentConnectionId, $callback);
		//Get Connected
        $this->_getConnection();
        //Get namespace
        $this->_namespace = $this->_getNamespace($appId);
    } 
	/**
     * Connect to memcache servers
     * 
     * If memcache is disabled then this method will return false
     * 
     * @access private
     * @param void
     * @throws Exception
     * @return {Boolean}
     */
    protected function _getConnection(){
		//Loop through the server and add server to memcached
		$servers = $this->_config['resource']['cache']['memcached']['server'];
		foreach ($servers as $server) {
    		if (!isset($server['host']) && !isset($server['port'])) {
    			throw new Exception("Memcached Error: Host IP and Port should be provided for connecting Host - {$server['host']} & Port {$server['port']}");
    		}
    		//Check server is already added to connection.
    		$addServer = TRUE;
    		$serversList = $this->getServerList();
    		if (count($serversList) > 0) {
				foreach ($serverList as $existingServer) {
					if (($existingServer['host'] == $server['host']) && ($existingServer['port'] == $server['port'])) {
							$addServer = FALSE;
					}
				}
    		}
    		if ($addServer) {
				if ($this->addServer($server['host'], $server['port'])) {
					$this->_connected = true;
				} else {
					throw new Exception("Memcached Error: Unable to connect with Server :  {$server['host']} on Port: {$server['port']}, with message: {$this->getResultMessage()}");
				}
    		}
		}
		return $this->_connected;
    }
    /**
	 * 
	 * Create unique namespace for cache data
     * Check namespace is already registered in cache
     * Namespace format:
     * 
     * FRAMEWORK:APP_ID
     * 
     * 
     * @access protected
     * @param {string} $appId
     * @return {string} $namespaceValue
     */
    public function _getNamespace($appId){
       $namespaceKey = $this->_namespaceFrameWork.':'.$appId;
       $namespaceValue = call_user_func('parent::get',$namespaceKey);
       //Check namespace exist else create new namespace
       if($namespaceValue === FALSE) {
           $namespaceValue = rand(999, 9999);
           if(call_user_func('parent::get',$namespaceKey,$namespaceValue) === FALSE){
               throw new Exception("Memcached Error: Unable to set namespace:  {$namespaceKey} and Value: {$namespaceValue} , with error: " . parent::getResultMessage());
           }
       }
       return $namespaceValue;
    }
    /**
     * Enable memcache
     * @access public
     * @param void
     * @return void
     */
    public static function enable(){
        self::$_isEnabled = TRUE;
    }
    /**
     * Disable memcache
     * @access public
     * @param void
     * @return void
     */
    public static function disable(){
        self::$_isEnabled = FALSE;
    }
    /**
     * Check cache is enabled or disabled
     * 
     * @access public
     * @param void
     * @return {Boolean}
     */
    public static function isEnabled(){
        return self::$_isEnabled;
    }
	/**
     * Enable persistent connection
     * @access public
     * @param void
     * @return void
     */
    public static function enablePersistentConnection(){
        self::$_isPersistentEnabled = TRUE;
    }
    /**
     * Disable persistent connection
     * @access public
     * @param void
     * @return void
     */
    public static function disablePersistentConnection(){
        self::$_isPersistentEnabled = FALSE;
    }
    /**
     * Check persistent connection is enabled or disabled
     * 
     * @access public
     * @param void
     * @return {Boolean}
     */
    public static function isPersistentConnectionEnabled(){
        return self::$_isPersistentEnabled;
    }
	/**
     * Get expiration time
     *
     * @access public
     * @param void
     * @return {string}
     */
	public function getExpirationTime(){
		return $this->_expirationTime;
	}
		
	/**
     * Set expiration time
     *
     * @access public
     * @param void
     * @return {string}
     */
	public function setExpirationTime($time){
		$this->_expirationTime = $time;
	}
		
    /**
     * Enable fallover
     *
     * @access public
     * @param void
     * @return {Boolean}
     */
    public function enableFallOver(){
        $this->_isFalloverEnabled = TRUE;
    }
    
    /**
     * Disable fallover
     *
     * @access public
     * @param void
     * @return {Boolean}
     */
    public function disableFallOver(){
        $this->_isFalloverEnabled = FALSE;
    }    
    
    /**
     * Get fallover status
     *
     * @access public
     * @param void
     * @return {Boolean}`
     */
    public function isFallOverEnabled(){
        return $this->_isFalloverEnabled;
    }        
    /**
     * Set value in memcached. If expiration time is not
     * specified it will default to DEFAULT_EXPIRE_TIME
     * 
     * @param {string} $key
     * @param {string} $value
     * @param {string} $expires , time default to DEFAULT_EXPIRE_TIME
     * @throws Exception
     * @return {boolean}
     */
    function set($key, $value, $expires = NULL) {
        if (!$expires) {
          //Override with application expiration time
		  $expires = $this->_expirationTime;
		}
        if(call_user_func('parent::set',$this->_namespace.':'. md5($key), $value, $expires)){
            return TRUE;
        }else{
            //Unable to set
            throw new Exception("Memcached Error: Unable to set key:  {$key} and Value: {$value} , with error: " . $this->getResultMessage());
        }
    }
    /**
     * Get a value from cache
     * 
     * @access public
     * @param {string} $key
     * @return {mixed}
     */
    public function get($key, $cache_cb = null, &$cas_token = null) {
		if($cas_token){
			return call_user_func('parent::get',$this->_namespace.':'. md5($key), $cache_cb, $$cas_token);	
		}else{
			return call_user_func('parent::get',$this->_namespace.':'. md5($key), $cache_cb, null);	
		}	
    }

    /**
     * Remove key value pair from cache
     * 
     * @access public
     * @param {string} $key
	 * @param {string} $time, The amount of time the server will wait to delete the item.
     * @return {mixed}
     */
    public function delete($key, $time = 0) {
        return call_user_func('parent::delete',$this->_namespace.':'. md5($key), $time);
    }
    
    /**
     * Remove all app data and will not be accessible
     * for any further calls
     *  
     * @access public
     * @param {void}
     * @return {mixed} 
     */
    public function deleteApp(){
        $namespaceKey = $this->_namespaceFrameWork.':'.$this->_appId;
        if ($this->increment($namespaceKey)) {
            $this->_namespace = $this->get($namespaceKey);
            return TRUE;
        }else{
            # Increment failed! Key must not exist.
            return FALSE;
        }
    }
    
}
