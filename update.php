<?php
require_once(dirname(__FILE__) . '/libs/Debugger/debugger.php');
MagentoDebugger::setDebuggerDir(dirname(__FILE__));

if (!isset($argv[1])){
    return;
}

$version = $argv[1];

require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/update.php');
try{
    MagentoDebugger_Update::run($version);
}
catch(Exception $e){
    echo "Error while updating: " . $e->getMessage . "\n";
}
?>