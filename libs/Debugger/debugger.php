<?php
abstract class MagentoDebugger{
    protected static $_configurations = null;
    
    public static function log($data = ""){
        echo $data . "\n";
    }
    
    public static function getPath($item){
        $item = str_replace('\\', '/', $item);
        return $item;
    }
    
    
    public static function isWritable($item){
        if (!is_dir($item)){
            return is_writable($item);
        }
        
        if (file_put_contents($item . '/temp.tmp', 'tmp')){
            unlink($item . '/temp.tmp');
            return true;
        }
        
        return false;
    }
    
    public static function setDebuggerDir($dir){
        $dir = self::getPath($dir);
        self::$_configurations['debugger_dir'] = $dir;
    }
    
    public static function getDebuggerDir(){
        return self::$_configurations['debugger_dir'];
    }
    
    public static function getDebuggerVarDir(){
        return static::getDebuggerDir() . '/var';
    }
    
    public static function setProjectDir($dir){
        self::$_configurations['project_dir'] = $dir;
    }
    
    public static function getProjectDir(){
        return self::$_configurations['project_dir'];
    }
    
    protected static $_projectInfo = null;
    
    protected static $_profilerEnabled = false;
    
    protected static $_uniqId = null;
    protected static $_jsonLog = null;
    
    public static function enableProfiler($status = true){
        self::$_profilerEnabled = $status;
        if (!$status){
            return;
        }
        
        require_once(MagentoDebugger::getProjectDir() . '/lib/Varien/Profiler.php');
        //require_once(self::getDebuggerDir() . '/libs/Varien/Profiler.php');
        Varien_Profiler::enable();
        
        $serverKey = MagentoDebugger::getKeyFromString(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'console');
        $time = time();
        $directory = MagentoDebugger::getDebuggerVarDir() . '/profiler/';
        
        self::$_uniqId = $time . '_' . uniqid();
        self::$_jsonLog = $directory . $serverKey . '.' . self::$_uniqId;
        $url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Not specified');
        
        if (!is_dir($directory)){
            mkdir($directory);
        }
        
        $profilerHeader = array(
            'time' => $time,
            'url'  => $url,
            'key'  => self::$_uniqId
        );
        
        file_put_contents(self::$_jsonLog . '.jshe', json_encode($profilerHeader));
    }
    
    public static function saveProfiler(){
        if (!self::$_profilerEnabled){
            return;
        }
        
        $timers = Varien_Profiler::getTimers();
        foreach($timers as $timerName => $timer){
            $timer['name'] = $timerName;
            $base64 = (
                json_encode($timer)
            );
            
            file_put_contents(self::$_jsonLog . '.jsar', $base64 . "\n", FILE_APPEND);
        }
    }
    
    public static function getKeyFromString($string){
        return preg_replace('/[^a-zA-Z0-9]/Usi', '_', $string);
    }
    
    public static function getProjectInfo(){
        if (self::$_projectInfo){
            return self::$_projectInfo;
        }
        
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Zend/Exception.php');
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Zend/Config/Exception.php');
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Zend/Config.php');
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Zend/Config/Ini.php');
        
        $currentHost = null;
        $currentHostName = $_SERVER['SERVER_NAME'];
        $dir = opendir(self::getDebuggerDir() . '/config');
        while($file = readdir($dir)){
            if (!is_file(self::getDebuggerDir() . '/config/' . $file)) continue;
            $fileInfo = pathinfo($file);
            if (!isset($fileInfo['extension']) || $fileInfo['extension']!='ini'){
                continue;
            }
            
            $config = new Zend_Config_Ini(self::getDebuggerDir() . '/config/' . $file, 'config');
            if ($config->name == $currentHostName){
                $currentHost = $config->toArray();
                $currentHost['identifier'] = $fileInfo['filename'];
                break;
            }
        }
        
        if (!$currentHost){
            return;
        }
        
        // Append data
        if (is_file(MagentoDebugger::getDebuggerDir() . '/var/' . $currentHost['identifier'] . '.project.json')){
            $extended = file_get_contents(MagentoDebugger::getDebuggerDir() . '/var/' . $currentHost['identifier'] . '.project.json');
            $currentHost['extended'] = (array) json_decode($extended);
        }
        
        MagentoDebugger::setProjectDir($currentHost['dir']);
        self::$_projectInfo = $currentHost;
        return $currentHost;
    }
    
    public static function prepareLibraries(){
        // Admin prepare
        require_once(self::getProjectDir() . '/app/code/core/Mage/Core/Helper/Abstract.php');
        
        // Block prepare
        require_once(self::getProjectDir() . '/lib/Varien/Object.php');
        require_once(self::getProjectDir() . '/app/code/core/Mage/Core/Block/Abstract.php');
        
        // Email prepare
        require_once(self::getProjectDir() . '/app/code/core/Mage/Core/Model/Abstract.php');
        require_once(self::getProjectDir() . '/app/code/core/Mage/Core/Model/Template.php');
        
        // Database prepare
        require_once(self::getProjectDir() . '/lib/Varien/Db/Adapter/Interface.php');
        require_once(self::getProjectDir() . '/lib/Varien/Db/Ddl/Table.php');
        require_once(self::getProjectDir() . '/lib/Zend/Db/Adapter/Abstract.php');
        require_once(self::getProjectDir() . '/lib/Zend/Db/Adapter/Pdo/Abstract.php');
        require_once(self::getProjectDir() . '/lib/Zend/Db/Adapter/Pdo/Mysql.php');
        require_once(self::getProjectDir() . '/lib/Zend/Db.php');
    }
    
    public static function iniMage(){
        chdir(self::getProjectDir());
        
        require self::getProjectDir() . '/app/Mage.php';
        
        Mage::setRoot(self::getProjectDir() . '/app');
        Mage::app('admin')->setUseSessionInUrl(false);
        Mage::setIsDeveloperMode(true);
        
        umask(0);
    }
    
    protected static $_configurationSaved = false;
    
    public static function saveConfiguration(){
        if (self::$_configurationSaved){
            return;
        }
        
        self::$_configurationSaved = true;
        
        $dataObject = new Varien_Object();
        $dataObject->setVarDir(Mage::getBaseDir('var'));
        
        $json = Mage::helper('core')->jsonEncode($dataObject);
        $projectInfo = self::getProjectInfo();
        $file = self::getDebuggerDir() . '/var/' . $projectInfo['identifier'] . '.project.json';
        file_put_contents($file, $json);
    }
    
    function removeDirectory($path) {
        if (!is_dir($path)){
            unlink($path);
            return;
        }
        
        $dirResource = opendir($path);
        
        while ($file = readdir($dirResource)) {
            if ($file == '..' || $file == '.'){
                continue;
            }
            
            $file = $path . '/' . $file;
            
            if (is_dir($file)){
                self::removeDirectory($file);
            }
            else{
                unlink($file);
            }
        }
        
        rmdir($path);
        return;
    }
    
    static public function copy($source, $dest, $mode = false)
    {
        if(is_dir($source)) {
            $dir_handle=opendir($source);
            $sourcefolder = basename($source);
            mkdir($dest."/".$sourcefolder);
            if ($mode){
                chmod($dest."/".$sourcefolder, $mode);
            }
            while($file=readdir($dir_handle)){
                if($file!="." && $file!=".."){
                    if(is_dir($source."/".$file)){
                        self::copy($source."/".$file, $dest."/".$sourcefolder);
                    } else {
                        copy($source."/".$file, $dest."/".$file);
                        if ($mode){
                            chmod($dest."/".$file, $mode);
                        }
                    }
                }
            }
            closedir($dir_handle);
        } else {
            copy($source, $dest);
            if ($mode){
                chmod($dest, $mode);
            }
        }
    }
}
