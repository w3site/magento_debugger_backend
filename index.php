<?php
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 *
 * @author Tereta Alexander (www.w3site.org)
 */

define('MAGENTO_DEBUGGER_VERSION', '0.0.1');

/**
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */

require_once('libs/Zend/Exception.php');
require_once('libs/Zend/Config/Exception.php');
require_once('libs/Zend/Config.php');
require_once('libs/Zend/Config/Ini.php');

define('MAGENTO_DEBUGGER_DIR', dirname(__FILE__));

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
        $currentHost = $config;
        break;
    }
}

define('MAGENTO_DEBUGGER_PROJECT_DIR', $currentHost->dir);

// Installation
if (!$currentHost || (isset($_GET['magento_debug']) && $_GET['magento_debug'] == 'configure')){
    require_once(MAGENTO_DEBUGGER_DIR . '/libs/Debugger/installation.php');
    return;
}

// XDebug
if (isset($_GET['XDEBUG_SESSION_START']) || isset($_GET['XDEBUG_SESSION_STOP_NO_EXEC'])){
    require_once(MAGENTO_DEBUGGER_DIR . '/libs/Debugger/xdebug.php');
    return;
}

// Admin prepare
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/app/code/core/Mage/Core/Helper/Abstract.php');

// Block prepare
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Varien/Object.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/app/code/core/Mage/Core/Block/Abstract.php');

// Email prepare
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/app/code/core/Mage/Core/Model/Abstract.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/app/code/core/Mage/Core/Model/Template.php');

// Database prepare
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Varien/Db/Adapter/Interface.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Varien/Db/Ddl/Table.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Zend/Db/Adapter/Abstract.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Zend/Db/Adapter/Pdo/Abstract.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Zend/Db/Adapter/Pdo/Mysql.php');
require_once(MAGENTO_DEBUGGER_PROJECT_DIR . '/lib/Zend/Db.php');

if (isset($_GET['magento_debug_info']) && $_GET['magento_debug_info'] == 'yes'){
    $debuggedInfo = new Varien_Object();
    $debuggedInfo->setVersion(MAGENTO_DEBUGGER_VERSION);
    echo json_encode($debuggedInfo->getData());
    return;
}

if (true){ // Email debug
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

// http://kamikv2.w3site.org/?magento_debug_action=model&magento_debug_model_method=kamik_newsletter/observer::confirmationReminder
// kamik_newsletter/observer::confirmationReminder
if (isset($_GET['magento_debug'])){
    if ($_GET['magento_debug'] == 'model' && isset($_GET['magento_debug_model_method'])){
        $modelMethodName = $_GET['magento_debug_model_method'];
        
        header('Content-Type: text/plain');
        require_once('libs/Debugger/model.php');
    }
}
else{
    chdir($currentHost->dir);
    require_once('index.php');
}
?>
