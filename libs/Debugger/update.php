<?php 
abstract class MagentoDebugger_Update{
    public static function run($version){
        echo "Starting update from the version " . MAGENTO_DEBUGGER_VERSION . " to the version " . $version . "...\n";
        $updateDir = MagentoDebugger::getDebuggerVarDir() . '/update';
        if (is_dir($updateDir)){
            MagentoDebugger::removeDirectory($updateDir);
        }
        mkdir($updateDir);
        
        // Downloading
        echo "Downloading update package...\n";
        $sourceUrl = 'https://github.com/w3site/magento_debugger_backend/archive/version-' . $version . '.zip';
        $saveFile = $updateDir . '/downloaded.zip';
        copy($sourceUrl, $saveFile);
        
        // Unzip
        echo "Preparing package to update...\n";
        $zip = new ZipArchive;
        $res = $zip->open($saveFile);
        $zip->extractTo($updateDir);
        $zip->close();
        
        // Prepare files
        $updateVersionDir = $updateDir . '/magento_debugger_backend-version-' . $version;
        $dirResource = opendir($updateVersionDir);
        $remove = self::$_excludeUpdateFiles;
        
        while($item = readdir($dirResource)){
            if ($item == '.' || $item == '..') continue;
            
            if (in_array($item, $remove)){
                MagentoDebugger::removeDirectory($updateVersionDir . '/' . $item);
            }
        }
        
        // Update
        echo "Updating...\n";
        $updatePath = MagentoDebugger::getDebuggerDir();
        $updatePath = '/home/tereta/Work/Server/MagentoDebugger_2';
        
        self::updateFiles($updateVersionDir, $updatePath, true);
        echo "Update has been finished sucefully.\n";
    }
    
    static protected $_excludeUpdateFiles = array('.gitignore', 'var', 'config', '.git', '.buildpath', '.project');
    
    static protected $_defaultDirectoryPermission = null;
    
    static public function updateFiles($source, $dest, $isRoot = false)
    {
        if ($isRoot){
            self::$_defaultDirectoryPermission = fileperms($dest);
        }
        
        // Clearing
        if (is_dir($source)){
            $dirResource=opendir($dest);
            while($file=readdir($dirResource)){
                if($file=="." || $file==".." || in_array($file, self::$_excludeUpdateFiles)){
                    continue;
                }
                
                $sourceObject = $source . '/' . $file;
                if (!is_file($sourceObject) && !is_dir($sourceObject)){
                    $destObject = $dest . '/' . $file;
                    MagentoDebugger::removeDirectory($destObject);
                }
            }
            closedir($dirResource);
        }
        
        // Copying
        if(is_dir($source)) {
            $dirResource=opendir($source);
            if (!$isRoot && !is_dir($dest)){
                mkdir($dest);
                chmod($dest, self::$_defaultDirectoryPermission);
            }
            
            while($file=readdir($dirResource)){
                if($file!="." && $file!=".."){
                    self::updateFiles($source . "/" . $file, $dest . "/" . $file);
                }
            }
            closedir($dirResource);
        } else {
            $permissions = self::$_defaultDirectoryPermission;
            if (file_exists($dest)){
                $permissions = fileperms($dest);
            }
            
            copy($source, $dest);
            if ($permissions){
                chmod($dest, $permissions);
            }
        }
    }
}
