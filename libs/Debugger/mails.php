<?php
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 *
 * @author Tereta Alexander (www.w3site.org)
 */

abstract class MagentoDebugger_Mails{
    public static function init(){
        switch ($_GET['magento_debug_action']){
            case("getlist"):
                self::getList();
                break;
            case("clearlist"):
                self::clearList();
                break;
        }
    }
    
    public static function clearList(){
        $mailDir = Mage::getBaseDir('var') . '/mails';
        $dir = opendir($mailDir);
        while($file = readdir($dir)){
            if (is_file($mailDir . '/' . $file)){
                unlink($mailDir . '/' . $file);
            }
        }
    }
    
    public static function getList(){
        $serverKey = MagentoDebugger::getKeyFromString($_SERVER['SERVER_NAME']);
        
        $mailDir = MagentoDebugger::getDebuggerVarDir() . '/mails';
        $dir = opendir($mailDir);
        $files = array();
        while($item = readdir($dir)){
            $pathinfo = pathinfo($item);
            if (!isset($pathinfo['extension']) || $pathinfo['extension'] != 'json'){
                continue;
            }
            
            $itemConfigurationString = file_get_contents($mailDir . '/' . $item);
            $itemConfiguration = (array) json_decode($itemConfigurationString);
            $itemConfiguration['identifier'] = $pathinfo['filename'];
            $itemConfiguration['time'] = filemtime($mailDir . '/' . $item);
            $itemConfiguration['datetime'] = @date('Y-m-d H:i:s', $itemConfiguration['time']);
            $files[$itemConfiguration['time'] . '_' . uniqid()] = $itemConfiguration;
        }
        sort($files);
        echo json_encode($files);
    }
}

MagentoDebugger_Mails::init();