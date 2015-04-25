<?php
require_once(dirname(__FILE__) . '/libs/Debugger/debugger.php');
MagentoDebugger::setDebuggerDir(dirname(__FILE__));
//$targetDir = '/home/tereta/Work/Server/MagentoDebugger_2';
$targetDir = MagentoDebugger::getDebuggerDir();

require_once(MagentoDebugger::getDebuggerDir() . '/libs/Debugger/update.php');

$filePermissions = fileperms(MagentoDebugger::getDebuggerVarDir() . '/required.version');
$dirPermissions = fileperms(MagentoDebugger::getDebuggerVarDir() . '/required.dir');
$owner = fileowner(MagentoDebugger::getDebuggerVarDir() . '/required.version');
$fixed = MagentoDebugger_Update::fixPermissions($targetDir, $owner, $filePermissions, $dirPermissions);
?>