<?php 
abstract class MagentoDebugger_Update{
    public static function run($version){
        return;
        $updateDir = MagentoDebugger::getDebuggerVarDir() . '/update';
        if (is_dir($updateDir)){
            MagentoDebugger::removeDirectory($updateDir);
        }
        mkdir($updateDir);
        
        $sourceUrl = 'https://github.com/w3site/magento_debugger_backend/archive/version-' . $version . '.zip';
        $saveFile = $updateDir . '/downloaded.zip';
        copy($sourceUrl, $saveFile);
        
        $zip = new ZipArchive;
        $res = $zip->open($saveFile);
        $zip->extractTo($updateDir);
        $zip->close();
        
        // Copying files
        $updateVersionDir = $updateDir . '/magento_debugger_backend-version-' . $version;
        $dirResource = opendir($updateVersionDir);
        $ignore = array('.', '..', '.gitignore', 'var', 'config');
        while($item = readdir($dirResource)){
            if (in_array($item, $ignore)){
                continue;
            }
            
            //MagentoDebugger::removeDirectory(MagentoDebugger::getDebuggerDir() . '/' . $item);
            MagentoDebugger::copy($updateVersionDir . '/' . $item, MagentoDebugger::getDebuggerDir() . '/' . $item, 0777);
        }
    }
}
