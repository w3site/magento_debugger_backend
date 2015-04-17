<?php 
abstract class MagentoDebugger_Update{
    public static function verifyPermissions($dir){
        $dirResource=opendir($dir);
        while($file=readdir($dirResource)){
            if($file!="." && $file!=".."){
                if (!is_writable($dir . "/" . $file)){
                    return false;
                }
                
                if (is_dir($dir . "/" . $file)){
                    if (!self::verifyPermissions($dir . "/" . $file)){
                        return false;
                    }
                }
            }
        }
        closedir($dirResource);
        
        return true;
    }
    
    public static function run($version = null){
        if (!$version && is_file(MagentoDebugger::getDebuggerVarDir() . '/required.version')){
            $version = file_get_contents(MagentoDebugger::getDebuggerVarDir() . '/required.version');
        }
        
        if (!$version){
            echo "Can not update version becouse version does not specified.\n";
            echo "Please run open chrome extension on the project to define version of Magento Debugger Chrome extension.\n";
            echo "Or specify the new version manualy usin command \"php update.php --version=[your version]\" (for example: \"php update.php --version=" . MAGENTO_DEBUGGER_VERSION . "\").\n";
            return;
        }
        
        $updatePath = MagentoDebugger::getDebuggerDir();
        //$updatePath = '/home/tereta/Work/Server/MagentoDebugger_2';
        
        if (!self::verifyPermissions($updatePath)){
            echo "Please use root account becouse for some files, the current user haven't permissions to write.\n";
            echo "Updating failed.\n";
            return;
        }
        
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
        $copyed = @copy($sourceUrl, $saveFile);
        if (!$copyed){
            echo "Error: " . error_get_last()['message'];
            echo "Updating failed.\n";
            return;
        }
        
        // Unzip
        if (!class_exists('ZipArchive')){
            echo "Error: ZipArchive class does not found, please install and configure php to work with this extension class.\n";
            echo "Updating failed.\n";
            return;
        }
        
        echo "Preparing package to update...\n";
        $zip = new ZipArchive;
        $res = $zip->open($saveFile);
        $zip->extractTo($updateDir);
        $zip->close();
        
        // Prepare files
        $updateVersionDir = $updateDir . '/magento_debugger_backend-version-' . $version;
        $dirResource = opendir($updateVersionDir);
        
        while($item = readdir($dirResource)){
            if ($item == '.' || $item == '..') continue;
            
            if (in_array($item, self::$_excludeUpdateFiles)){
                MagentoDebugger::removeDirectory($updateVersionDir . '/' . $item);
            }
        }
        
        // Update
        echo "Updating...\n";
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
                if($file=="." || 
                        $file==".." || 
                        ($isRoot && in_array($file, self::$_excludeUpdateFiles))
                        ){
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
