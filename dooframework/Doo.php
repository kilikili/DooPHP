<?php

namespace Doo;

use Doo\App\DooConfig;
use Doo\DB\DooSqlMagic;
use Doo\DB\DooMasterSlave;
use Doo\Session\DooSession;
use Doo\Session\DooCacheSession;
use Doo\App\DooWebApp as DooWebApp;
use Doo\Auth\DooAcl;
use Doo\Translate\DooTranslator;
use Doo\Logging\DooLog;
use Doo\Cache\DooFileCache;

use Doo\Cache\DooPhpCache;
use Doo\Cache\DooFrontCache;
use Doo\Cache\DooApcCache;
use Doo\Cache\DooXCache;
use Doo\Cache\DooEAcceleratorCache;
use Doo\Cache\DooMemCache;

/**
 * Doo class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.doophp.com/license
 * @version $Id: DooWebApp.php 1000 2009-06-22 18:27:22
 * @package doo
 * @since 1.0
 */

/**
 * Doo is a singleton class serving common framework functionalities.
 *
 * You can access Doo in every class to retrieve configuration settings,
 * DB connections, application properties, logging, loader utilities and etc.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: Doo.php 1000 2009-07-7 18:27:22
 * @package doo
 * @since 1.0
 */
class Doo{
    protected static $_app;
    protected static $_conf;
    protected static $_logger;
    protected static $_db;
    protected static $_useDbReplicate;
    protected static $_cache;
    protected static $_acl;
	protected static $_session;
	protected static $_translator;
    protected static $_globalApps;
    protected static $_autoloadClassMap;    
    protected static $_dbs = array();

    /**
     * @return DooConfig configuration settings defined in <i>common.conf.php</i>, auto create if the singleton has not been created yet.
     */
    public static function conf(){
        if(self::$_conf===NULL){
            self::$_conf = new DooConfig;
        }
        return self::$_conf;
    }
    
    /**
     * Set the list of Doo applications. 
     * <code>
     * //by default, Doo::loadModelFromApp() will load from this application path
     * $apps['default'] = '/var/path/to/shared/app/'
     * $apps['app2'] = '/var/path/to/shared/app2/'
     * $apps['app3'] = '/var/path/to/shared/app3/' 
     * </code>
     * @param array $apps 
     */
    public static function setGlobalApps($apps){
        self::$_globalApps = $apps;
    }
    
    /**
     * Imports the definition of Model class(es) from a Doo application
     * @param string|array $modelName Name(s) of the Model class to be imported
     * @param string $appName Name of the application to be loaded from
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadModelFromApp($modelName, $appName='default', $createObj=false){
		$class = $_globalApps[$appName];
        return self::load($modelName, self::$class . 'model/', $createObj);
    }
    
    /**
     * Imports the definition of User defined class(es) from a Doo application
     * @param string|array $className Name(s) of the Model class to be imported
     * @param string $appName Name of the application to be loaded from
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadClassFromApp($className, $appName='default', $createObj=false){
		$class = $_globalApps[$appName];
        return self::load($className, self::$class . 'class/', $createObj);
    }
    
    /**
     * Imports the definition of Controller class from a Doo application
     * @param string $class_name Name of the class to be imported
     */
    public static function loadControllerFromApp($controllerName, $appName='default'){
		$class = $_globalApps[$appName];
        return self::load($controllerName, self::$class . 'controller/');
    }

    /**
	 * @param string $appType The type of application you want. Options are: 'DooWebApp' and 'DooCliApp'
     * @return DooWebApp|DooCliApp the application singleton, auto create if the singleton has not been created yet.
     */
    public static function app($appType='DooWebApp'){
        if(self::$_app===NULL){
			$class = "\\Doo\\App\\" . $appType;
            self::$_app = new $class;
        }
        return self::$_app;
    }

    /**
	 * @param string $class the class to use for ACL. Can be DooAcl or DooRbAcl
     * @return DooAcl|DooRbAcl the application ACL singleton, auto create if the singleton has not been created yet.
     */
    public static function acl($class = 'DooAcl'){
        if(self::$_acl===NULL){
			$class = "\\Doo\\Auth\\" . $class;
            self::$_acl = new $class;
        }
        return self::$_acl;
    }

    /**
     * Call this method to use database replication instead of a single db server.
     */
    public static function useDbReplicate(){
        self::$_useDbReplicate = true;
    }

       
    //copy from Doo.php, and modified it
    public static function db($dbname='APP'){       
        if(!array_key_exists($dbname,self::$_dbs) || self::$_dbs[$dbname]===NULL){
            if(self::$_useDbReplicate===NULL){
                self::$_dbs[$dbname] = new DooSqlMagic;
            }else{
                self::$_db = new DooMasterSlave;
            }
        }
        if(!self::$_dbs[$dbname]->connected)
            self::$_dbs[$dbname]->connect();
        return self::$_dbs[$dbname];
    }

    /**
     * @return DooSession
     */
    public static function session($namespace = null){
        if(self::$_session===NULL){
            self::$_session = new DooSession($namespace);
        }
        return self::$_session;
    }
	
	/**
     * @return true/false according to cache system being installed
     */
    public static function cacheSession($prefix = 'dooSession/', $type='file'){
		$cache = self::cache($type);
		return DooCacheSession::installOnCache($cache, $prefix);
    }

	 /**
	  * @return DooTranslator
	  */
    public static function translator($adapter, $data, $options=array()) {
        if(self::$_translator===NULL){
            self::$_translator = new DooTranslator($adapter, $data, $options);
        }
        return self::$_translator;
    }

	/**
	 * Simple accessor to Doo Translator class. You must be sure you have initialised it before calling. See translator(...)
	 * @return DooTranslator
	 */
	public static function getTranslator() {
		return self::$_translator;
	}

    /**
     * @return DooLog logging tool for logging, tracing and profiling, singleton, auto create if the singleton has not been created yet.
     */
    public static function logger(){
        if(self::$_logger===NULL){
            self::$_logger = new DooLog(self::conf()->DEBUG_ENABLED);
        }
        return self::$_logger;
    }

    /**
     * @param string $cacheType Cache type: file, php, front, apc, memcache, xcache, eaccelerator. Default is file based cache.
     * @return DooFileCache|DooPhpCache|DooFrontCache|DooApcCache|DooMemCache|DooXCache|DooEAcceleratorCache file/php/apc/memcache/xcache/eaccelerator & frontend caching tool, singleton, auto create if the singleton has not been created yet.
     */
    public static function cache($cacheType='file') {
        if($cacheType=='file'){
            if(isset(self::$_cache['file']))
                return self::$_cache['file'];

            self::$_cache['file'] = new DooFileCache;
            return self::$_cache['file'];
        }
        else if($cacheType=='php'){
            if(isset(self::$_cache['php']))
                return self::$_cache['php'];

            self::$_cache['php'] = new DooPhpCache;
            return self::$_cache['php'];
        }
        else if($cacheType=='front'){
            if(isset(self::$_cache['front']))
                return self::$_cache['front'];

            self::$_cache['front'] = new DooFrontCache;
            return self::$_cache['front'];
        }
        else if($cacheType=='apc'){
            if(isset(self::$_cache['apc']))
                return self::$_cache['apc'];

            self::$_cache['apc'] = new DooApcCache;
            return self::$_cache['apc'];
        }
        else if($cacheType=='xcache'){
            if(isset(self::$_cache['xcache']))
                return self::$_cache['xcache'];

            self::$_cache['xcache'] = new DooXCache;
            return self::$_cache['xcache'];
        }
        else if($cacheType=='eaccelerator'){
            if(isset(self::$_cache['eaccelerator']))
                return self::$_cache['eaccelerator'];

            self::$_cache['eaccelerator'] = new DooEAcceleratorCache;
            return self::$_cache['eaccelerator'];
        }
        else if($cacheType=='memcache'){
            if(isset(self::$_cache['memcache']))
                return self::$_cache['memcache'];

            self::$_cache['memcache'] = new DooMemCache(Doo::conf()->MEMCACHE);
            return self::$_cache['memcache'];
        }
    }

    /**
     * Imports the definition of class(es) and tries to create an object/a list of objects of the class.
     * @param string|array $class_name Name(s) of the class to be imported
     * @param string $path Path to the class file
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object of the class name passed in.
     */
    protected static function load($class_name, $path, $createObj=FALSE){
        if(is_string($class_name)===True){
			$pure_class_name = basename($class_name);
            class_exists($pure_class_name, false)===True || require_once($path . "$class_name.php");
            if($createObj)
                return new $pure_class_name;
        }else if(is_array($class_name)===True){
            //if not string, then a list of Class name, require them all.
            //make sure the class_name has array with is_array
            if($createObj)
                $obj=array();

            foreach ($class_name as $one) {
				$pure_class_name = basename($one);
                class_exists($pure_class_name, false)===True || require_once($path . "$one.php");
                if($createObj)
                    $obj[] = new $pure_class_name;
            }

            if($createObj)
                return $obj;
        }
    }

    /**
     * Imports the definition of User defined class(es). Class file is located at <b>SITE_PATH/protected/class/</b>
     * @param string|array $class_name Name(s) of the class to be imported
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadClass($class_name, $createObj=FALSE){
        return self::load($class_name, self::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER . "class/", $createObj);
    }

    /**
     * Imports the definition of Controller class. Class file is located at <b>SITE_PATH/protected/controller/</b>
     * @param string $class_name Name of the class to be imported
     */
    public static function loadController($class_name){
		return self::load($class_name, self::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER . 'Controller/', false);
    }

    /**
     * Imports the definition of Model class(es). Class file is located at <b>SITE_PATH/protected/model/</b>
     * @param string|array $class_name Name(s) of the Model class to be imported
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadModel($class_name, $createObj=FALSE){
        return self::load($class_name, self::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER . 'Model/', $createObj);
    }

    /**
     * Imports the definition of Helper class(es). Class file is located at <b>BASE_PATH/protected/helper/</b>
     * @param string|array $class_name Name(s) of the Helper class to be imported
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadHelper($class_name, $createObj=FALSE){
		//$class_name = "\\Doo\\Helper\\" . $class_name;
        //return self::load($class_name, self::conf()->BASE_PATH ."helper/", $createObj);
    }

    /**
     * Imports the definition of Doo framework core class. Class file is located at <b>BASE_PATH</b>.
     * @example If the file is in a package, called <code>loadCore('auth/DooLog')</code>
     * @param string $class_name Name of the class to be imported
     */
    public static function loadCore($class_name){
        //require_once self::conf()->BASE_PATH ."$class_name.php";
    }

    /**
     * Imports the definition of Model class(es) in a certain module or from the main app.
     *
     * @param string|array $class_name Name(s) of the Model class to be imported
     * @param string $path module folder name. Default is the main app folder.
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadModelAt($class_name, $moduleFolder=Null, $createObj=FALSE){
        if($moduleFolder===null){
            $moduleFolder = Doo::getAppPath();
        }else{
            $moduleFolder = Doo::getAppPath() . 'module/' . $moduleFolder;            
        }
        return self::load($class_name, $moduleFolder . "/Model/", $createObj);
    }

    /**
     * Imports the definition of Controller class(es) in a certain module or from the main app.
     *
     * @param string|array $class_name Name(s) of the Controller class to be imported
     * @param string $path module folder name. Default is the main app folder.
     */
    public static function loadControllerAt($class_name, $moduleFolder=Null){
        if($moduleFolder===null){
            $moduleFolder = Doo::getAppPath();
        }else{
            $moduleFolder = Doo::getAppPath() . 'module/' . $moduleFolder;            
        }        
		require_once $moduleFolder . '/Controller/'.$class_name.'.php';
    }

    /**
     * Imports the definition of User defined class(es) in a certain module or from the main app.
     *
     * @param string|array $class_name Name(s) of the class to be imported
     * @param string $path module folder name. Default is the main app folder.
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadClassAt($class_name, $moduleFolder=Null, $createObj=FALSE){
        if($moduleFolder===null){
            $moduleFolder = Doo::getAppPath();
        }else{
            $moduleFolder = Doo::getAppPath() . 'module/' . $moduleFolder;            
        }
        return self::load($class_name, $moduleFolder. "/class/", $createObj);
    }

    /**
     * Loads template tag class from plugin directory for both main app and modules
     * 
     * @param string $class_name Template tag class name
     * @param string $moduleFolder Folder name of the module. If Null, the class will be loaded from main app.
     */
    public static function loadPlugin($class_name, $moduleFolder=Null){
        if($moduleFolder===null){
            require_once Doo::getAppPath() . 'plugin/'. $class_name .'.php';
        }else{
            require_once Doo::getAppPath() .'module/'. $moduleFolder .'/plugin/'. $class_name .'.php';
        }
    }
	
    /**
     * Provides auto loading feature. To be used with the Magic method __autoload
     * @param string $classname Class name to be loaded.
     */
    public static function autoload($classname){
//        if( class_exists($classname, false) === true )
//			return;
        
        //App
		$class['DooConfig']      = 'App/DooConfig';
		$class['DooSiteMagic']   = 'App/DooSiteMagic';
		$class['DooWebApp']      = 'App/DooWebApp';
        
        //Auth
		$class['DooAcl']         = 'Auth/DooAcl';
		$class['DooAuth']        = 'Auth/DooAuth';
		$class['DooDigestAuth']  = 'Auth/DooDigestAuth';
		$class['DooRbAcl']       = 'Auth/DooRbAcl';    
        
        //Cache
		$class['DooApcCache']            = 'Cache/DooApcCache';
		$class['DooEAcceleratorCache']   = 'Cache/DooEAcceleratorCache';
		$class['DooFileCache']           = 'Cache/DooFileCache';
		$class['DooFrontCache']          = 'Cache/DooFrontCache';
		$class['DooMemCache']            = 'Cache/DooMemCache';
		$class['DooPhpCache']            = 'Cache/DooPhpCache';
		$class['DooXCache']              = 'Cache/DooXCache';
            
        //controller
		$class['DooController'] = 'Controller/DooController';
        
        //DB
		$class['DooDbExpression']    = 'DB/DooDbExpression';
		$class['DooMasterSlave']     = 'DB/DooMasterSlave';
		$class['DooModel']           = 'DB/DooModel';
		$class['DooModelGen']        = 'DB/DooModelGen';
		$class['DooSmartModel']      = 'DB/DooSmartModel';
		$class['DooSqlMagic']        = 'DB/DooSqlMagic';
        
        //DB/manage
		$class['DooDbUpdater']       = 'DB/manage/DooDbUpdater';
		$class['DooManageDb']        = 'DB/manage/DooManageDb';
		$class['DooManageMySqlDb']   = 'DB/manage/adapters/DooManageMySqlDb';
		$class['DooManagePgSqlDb']   = 'DB/manage/adapters/DooManagePgSqlDb';
		$class['DooManageSqliteDb']  = 'DB/manage/adapters/DooManageSqliteDb';
        
        //Helper
		$class['DooBenchmark']       = 'Helper/DooBenchmark';
		$class['DooFile']            = 'Helper/DooFile';
		$class['DooFlashMessenger']  = 'Helper/DooFlashMessenger';
		$class['DooForm']            = 'Helper/DooForm';
		$class['DooGdImage']         = 'Helper/DooGdImage';
		$class['DooMailer']          = 'Helper/DooMailer';
		$class['DooPager']           = 'Helper/DooPager';
		$class['DooRestClient']      = 'Helper/DooRestClient';
		$class['DooTextHelper']      = 'Helper/DooTextHelper';
		$class['DooTimezone']        = 'Helper/DooTimezone';
		$class['DooUrlBuilder']      = 'Helper/DooUrlBuilder';
		$class['DooValidator']       = 'Helper/DooValidator';
        
        //logging
		$class['DooLog'] = 'Logging/DooLog';
        
        //session
		$class['DooCacheSession'] = 'Session/DooCacheSession';
		$class['DooSession']      = 'Session/DooSession';      
        
        //translate
		$class['DooTranslator'] = 'Translate/DooTranslator';
        
        //uri
		$class['DooLoader'] = 'URI/DooLoader';
		$class['DooUriRouter'] = 'URI/DooUriRouter';
        
        //view
		$class['DooView'] = 'View/DooView';
		$class['DooViewBasic'] = 'View/DooViewBasic';
        
        if(isset($class[$classname]))
            self::loadCore($class[$classname]);
        else{ 
            if(isset(Doo::conf()->PROTECTED_FOLDER_ORI)===true){
                $path = Doo::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER_ORI;
            }else{
                $path = Doo::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER;                            
            }
            
            if(empty(Doo::conf()->AUTOLOAD)===false){
                if(Doo::conf()->APP_MODE=='dev'){
                    $includeSub = Doo::conf()->AUTOLOAD;
                    $rs = array();
                    foreach($includeSub as $sub){
                        if(file_exists($sub)===false){     
                            if(file_exists($path. $sub)===true){
                                $rs = array_merge($rs, DooFile::getFilePathList($path. $sub . '/') );                
                            }
                        }else{
                            $rs = array_merge($rs, DooFile::getFilePathList( $sub . '/') );                
                        }
                    }

                    $autoloadConfigFolder = $path . 'config/autoload/';

                    $rsExisting = null;

                    if(file_exists($autoloadConfigFolder.'autoload.php')===true){
                        $rsExisting = include($autoloadConfigFolder.'autoload.php');
                    }

                    if($rs != $rsExisting){
                        if(!file_exists($autoloadConfigFolder)){
                            mkdir($autoloadConfigFolder);
                        }
                        file_put_contents($autoloadConfigFolder.'autoload.php', '<?php return '.var_export($rs, true) . ';');                    
                    }                                
                }
                else{
					if(isset(self::$_autoloadClassMap)===false)
						$rs = self::$_autoloadClassMap = include_once($path . 'config/autoload/autoload.php');
					else
						$rs = self::$_autoloadClassMap;
                }
				
                if( isset($rs[$classname . '.php'])===true ){
                    require_once $rs[$classname . '.php'];
                    return;
                }
            }            
            
            //autoloading namespaced class                
            if(isset(Doo::conf()->APP_NAMESPACE_ID)===true && strpos($classname, '\\')!==false){
                $pos = strpos($classname, Doo::conf()->APP_NAMESPACE_ID);
                if($pos===0){
                    $classname = str_replace('\\','/',substr($classname, strlen(Doo::conf()->APP_NAMESPACE_ID)+1));
                    require_once $path . $classname . '.php';
                }
            }
        }
    }
    
    /**
     * Get the path where the Application source is located.
     * @return string
     */
    public static function getAppPath(){
        if(isset(Doo::conf()->PROTECTED_FOLDER_ORI)===true){
            return Doo::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER_ORI;
        }else{
            return Doo::conf()->SITE_PATH . Doo::conf()->PROTECTED_FOLDER;                            
        }        
    }

    /**
     * Simple benchmarking. To used this, set <code>$config['START_TIME'] = microtime(true);</code> in <i>common.conf.php</i> .
     * @param bool $html To return the duration as string in HTML comment.
     * @return mixed Duration(sec) of the benchmarked process. If $html is True, returns string <!-- Generated in 0.002456 seconds -->
     */
    public static function benchmark($html=false){
        if(!isset(self::conf()->START_TIME)){
            return 0;
        }
        $duration = microtime(true) - self::conf()->START_TIME;
        if($html)
            return '<!-- Generated in ' . $duration . ' seconds -->';
        return $duration;
    }

    public static function powerby(){
        return 'Powered by <a href="http://www.doophp.com/">DooPHP Framework</a>.';
    }

    public static function version(){
        return '1.4.1';
    }
    
    public static function setSqlTracking($enabled = false){
        foreach(self::$_dbs as $key => $obj) {
             $obj->sql_tracking = $enabled;
        }   
    }
    
    public static function sql2log() {
        foreach(self::$_dbs as $key => $obj) {
            $sqlList = $obj->showSQL();
            if (is_array($sqlList)) {
                foreach($sqlList as $sql) {
                    \Doo\Doo::logger()->log($sql, DooLog::PROFILE, 'SQL');
                }
            }
        }      
    }
}
