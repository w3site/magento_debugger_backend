<?php
abstract class MagentoDebugger{
    protected static $_configurations = null;
    
    public static function log($data = ""){
        echo $data . "\n";
    }
    
    public static function setDebuggerDir($dir){
        self::$_configurations['debugger_dir'] = $dir;
    }
    
    public static function getDebuggerDir(){
        return self::$_configurations['debugger_dir'];
    }
    
    public static function setProjectDir($dir){
        self::$_configurations['project_dir'] = $dir;
    }
    
    public static function getProjectDir(){
        return self::$_configurations['project_dir'];
    }
    
    public static function getProjectInfo(){
        $currentHost = null;
        $currentHostName = $_SERVER['SERVER_NAME'];
        $dir = opendir('config');
        while($file = readdir($dir)){
            if (!is_file('config/' . $file)) continue;
            $fileInfo = pathinfo($file);
            if (!isset($fileInfo['extension']) || $fileInfo['extension']!='ini'){
                continue;
            }
            
            $config = new Zend_Config_Ini('config/' . $file, 'config');
            if ($config->name == $currentHostName){
                $currentHost = $config->toArray();
                $currentHost['identifier'] = $file;
                break;
            }
        }
        
        MagentoDebugger::setProjectDir($currentHost['dir']);
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
}
