<?php
require_once(dirname(__FILE__) . '/libs/Debugger/debugger.php');
//MagentoDebugger::setDebuggerDir(dirname(__FILE__));
MagentoDebugger::setDebuggerDir('/home/tereta/Work/Server/MagentoDebugger_2');

require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/update.php');

$permissions = fileperms(MagentoDebugger::getDebuggerVarDir() . '/required.version');
MagentoDebugger_Update::fixPermissions(MagentoDebugger::getDebuggerDir(), $permissions);
?>