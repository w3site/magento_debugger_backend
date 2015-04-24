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
            case("getdata"):
                self::getData();
                break;
        }
    }
    
    public static function getData(){
         $serverKey = MagentoDebugger::getKeyFromString($_SERVER['SERVER_NAME']);
         $profileKey = isset($_GET['magento_debug_profiler_key']) ? $_GET['magento_debug_profiler_key'] : '';
         $profilerDir = MagentoDebugger::getDebuggerVarDir() . '/profiler';
         $data = array();
         
         $profilerFile = $profilerDir . '/' . $serverKey . '.' . $profileKey . '.jsar';
         
         if (!is_file($profilerFile)){
             return;
         }
         
         $dataJsonArray = file_get_contents($profilerFile);
         $dataJsonExploded = explode("\n", $dataJsonArray);
         array_pop($dataJsonExploded);
         
         foreach($dataJsonExploded as $item){
             array_push($data, json_decode(($item)));
         }
         
         echo json_encode($data);
    }
    
    public static function getList(){
        $serverKey = MagentoDebugger::getKeyFromString($_SERVER['SERVER_NAME']);
        
        $profilerDir = MagentoDebugger::getDebuggerVarDir() . '/profiler';
        $dir = opendir($profilerDir);
        $files = array();
        while($item = readdir($dir)){
            $pathinfo = pathinfo($item);
            if (!isset($pathinfo['extension']) || $pathinfo['extension'] != 'jshe'){
                continue;
            }
            
            if (substr($item, 0, strlen($serverKey) + 1) != $serverKey . '.'){
                continue;
            }
            
            $headerJson = file_get_contents($profilerDir . '/' . $item);
            $header = json_decode(trim($headerJson));
            $time = $header->time;
            $header->time = @date('Y.m.d H:i:s', $time);
            $files[$time] = $header;
        }
        sort($files);
        echo json_encode($files);
    }
}

//MagentoDebugger::iniMage();
MagentoDebugger_Mails::init();