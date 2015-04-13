<?php
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 *
 * @author Tereta Alexander (www.w3site.org)
 */

abstract class MagentoDebugger_Mysql{
    protected static $_projectInfo = null;
    
    public static function init(){
        self::$_projectInfo = MagentoDebugger::getProjectInfo();
        
        switch ($_GET['magento_debug_action']){
            case("getmessages"):
                self::getMessages();
                break;
            case("clearmessages"):
                self::clearMessages();
                break;
        }
    }
    
    public static function clearMessages(){
        $varDir = MagentoDebugger::getProjectInfo()['extended']['var_dir'];
        $mysqlFile = $varDir . '/debug/pdo_mysql.log';
        file_put_contents($mysqlFile, '');
        echo "success";
    }
    
    public static function getMessages(){
        $varDir = MagentoDebugger::getProjectInfo()['extended']['var_dir'];
        $mysqlFile = $varDir . '/debug/pdo_mysql.log';
        echo file_get_contents($mysqlFile);
    }
}

MagentoDebugger_Mysql::init();