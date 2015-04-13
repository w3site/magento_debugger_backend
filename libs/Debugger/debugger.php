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
    
    protected static $_projectInfo = null;
    
    public static function getProjectInfo(){
        if (self::$_projectInfo){
            return self::$_projectInfo;
        }
        
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
                $currentHost['identifier'] = $fileInfo['filename'];
                break;
            }
        }
        
        // Append data
        $extended = file_get_contents(MagentoDebugger::getDebuggerDir() . '/var/' . $currentHost['identifier'] . '.project.json');
        $currentHost['extended'] = (array) json_decode($extended);
        
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
}
