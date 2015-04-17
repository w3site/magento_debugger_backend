<?php
/*********************************************************************************
 * Magento Debugger version is */ define('MAGENTO_DEBUGGER_VERSION', '0.0.7'); /**
 *********************************************************************************
 *********************************************************************************
 * © Tereta Alexander (www.w3site.org), 2014-2015yy.                             *
 * All rights reserved.                                                          *
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************
 *********************************************************************************/

require_once('libs/Zend/Exception.php');
require_once('libs/Zend/Config/Exception.php');
require_once('libs/Zend/Config.php');
require_once('libs/Zend/Config/Ini.php');

require_once(dirname(__FILE__) . '/libs/Debugger/debugger.php');
MagentoDebugger::setDebuggerDir(dirname(__FILE__));

// Updater
if (defined('MAGENTO_DEBUGGER_UPDATE')){
    require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/update.php');
    $arguments = $argv;
    array_shift($arguments);
    $version = null;
    foreach($arguments as $item){
        $itemArray = explode('=', $item);
        if (isset($itemArray[0]) && $itemArray[0]=='--version' && isset($itemArray[1])){
            $version = $itemArray[1];
        }
    }
    
    $updateData = MagentoDebugger_Update::run($version);
    return;
}

$currentHost = MagentoDebugger::getProjectInfo();

// Installation
if (!$currentHost || (isset($_GET['magento_debug']) && $_GET['magento_debug'] == 'configure')){
    require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/installation.php');
    return;
}

if (!is_dir(MagentoDebugger::getProjectDir())){
    header('Location: /?magento_debug=configure');
    return;
}

MagentoDebugger::prepareLibraries();

// XDebug
if (isset($_GET['XDEBUG_SESSION_START']) || isset($_GET['XDEBUG_SESSION_STOP_NO_EXEC'])){
    require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/xdebug.php');
    return;
}

// Debugger info
if (isset($_GET['magento_debug_info']) && isset($_GET['current_version'])){
    $currentVersion = MAGENTO_DEBUGGER_VERSION;
    
    if ($_GET['current_version'] != MAGENTO_DEBUGGER_VERSION){
        file_put_contents(MagentoDebugger::getDebuggerVarDir() . '/required.version', trim($_GET['current_version']));
    }
    
    $debuggedInfo = new Varien_Object();
    $debuggedInfo->setVersion(MAGENTO_DEBUGGER_VERSION);
    echo json_encode($debuggedInfo->getData());
    return;
}

// Controller debug
if (isset($_COOKIE['magento_debug_controller']) && $_COOKIE['magento_debug_controller'] == 'yes'){
    require_once('libs/Mage/Core/Controller/Varien/Action.php');
}

// Email debug
if (isset($_COOKIE['magento_debug_mails']) && $_COOKIE['magento_debug_mails'] == 'yes'){
    require_once('libs/Mage/Core/Model/Email/Template.php');
}

// Allow all passwords for admin
if (isset($_COOKIE['magento_debug_password_admin']) && $_COOKIE['magento_debug_password_admin'] == 'yes'){
    require_once('libs/Mage/Core/Helper/Data.php');
}

// MySQL debug
$debugMysql = false;
if (isset($_COOKIE['magento_debug_mysql']) && $_COOKIE['magento_debug_mysql'] == 'value'){
    $debugMysql = true;
}

if (isset($_COOKIE['magento_debug_mysql']) && $_COOKIE['magento_debug_mysql'] == 'all'){
    $debugMysql = true;
}

if ($debugMysql){
    require_once('libs/Varien/Db/Adapter/Pdo/Mysql.php');
    
    if (isset($_COOKIE['magento_debug_mysql_trace']) && $_COOKIE['magento_debug_mysql_trace'] == 'yes'){
        Varien_Db_Adapter_Pdo_Mysql::setLogCallStack();
    }
    
    if ($_COOKIE['magento_debug_mysql'] == 'all'){
        Varien_Db_Adapter_Pdo_Mysql::setLogQueryTime();
    }
    
    if ($_COOKIE['magento_debug_mysql'] == 'value' && isset($_COOKIE['magento_debug_mysql'])){
        Varien_Db_Adapter_Pdo_Mysql::setLogQueryTime((float) $_COOKIE['magento_debug_mysql']);
    }
}

// Blocks debug
if (isset($_COOKIE['magento_debug_blocks']) && $_COOKIE['magento_debug_blocks'] == 'yes'){
    require_once('libs/Mage/Core/Block/Template.php');
}

if (isset($_GET['magento_debug'])){
    if ($_GET['magento_debug'] == 'file' && isset($_GET['magento_debug_file'])){
        $dir = MagentoDebugger::getDebuggerDir() . '/files/';
        $file = realpath($dir . $_GET['magento_debug_file']);
        
        if ($file && substr($file, 0, strlen($dir)) == $dir){
            echo file_get_contents($file);
        }
        else{
            header("HTTP/1.0 404 Not Found");
            echo "Error 404.";
        }
    }
    
    if ($_GET['magento_debug'] == 'model' && isset($_GET['magento_debug_model_method'])){
        $modelMethodName = $_GET['magento_debug_model_method'];
        
        header('Content-Type: text/plain');
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/model.php');
    }
    
    if ($_GET['magento_debug'] == 'maillist' && isset($_GET['magento_debug_action'])){
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/mails.php');
    }
    
    if ($_GET['magento_debug'] == 'mysql' && isset($_GET['magento_debug_action'])){
        require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/mysql.php');
    }
}
else{
    chdir(MagentoDebugger::getProjectDir());
    require_once('index.php');
    MagentoDebugger::saveConfiguration();
}
?>
