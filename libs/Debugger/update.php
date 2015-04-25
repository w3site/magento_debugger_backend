<?php 
abstract class MagentoDebugger_Update{
    const ERROR_PRIVILEGES = 1;
    const ERROR_VERSION_NOT_SPECIFIED = 2;
    const ERROR_CAN_NOT_DOWNLOAD = 3;
    const ERROR_ZIP = 4;
    
    static protected $_excludeUpdateFiles = array('.gitignore', 'var', 'config', '.git', '.buildpath', '.project');
    static protected $_defaultDirectoryPermission = null;
    
    public static function fixPermissions($dir, $privileges){
        $dirResource=opendir($dir);
        while($file=readdir($dirResource)){
            if($file!="." && $file!=".."){
    			if (is_dir($dir . "/" . $file)){
    				chmod($dir . "/" . $file, $privileges);
    				self::fixPermissions($dir . "/" . $file, $privileges);
    			}
    			else{
    				chmod($dir . "/" . $file, $privileges);
    			}
    		}
    	}
    	closedir($dirResource);
    }
    
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
        if (!$version){
            throw new Exception('Can not update version becouse version does not specified.', self::ERROR_VERSION_NOT_SPECIFIED);
        }
        
        $updatePath = MagentoDebugger::getDebuggerDir();
        $updatePath = '/home/tereta/Work/Server/MagentoDebugger_2';
        
        if (!self::verifyPermissions($updatePath)){
            throw new Exception("Wrong permitions for files to update", self::ERROR_PRIVILEGES);
        }
        
        $updateDir = MagentoDebugger::getDebuggerVarDir() . '/update';
        if (is_dir($updateDir)){
            MagentoDebugger::removeDirectory($updateDir);
        }
        mkdir($updateDir);
        
        // Downloading
        $sourceUrl = 'https://github.com/w3site/magento_debugger_backend/archive/version-' . $version . '.zip';
        $saveFile = $updateDir . '/downloaded.zip';
        $copyed = @copy($sourceUrl, $saveFile);
        if (!$copyed){
            throw new Exception(error_get_last()['message'], self::ERROR_CAN_NOT_DOWNLOAD);
        }
        
        // Unzip
        if (!class_exists('ZipArchive')){
            throw new Exception('ZipArchive class does not found, please install and configure php to work with this extension class.', self::ERROR_ZIP);
        }
        
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
        self::updateFiles($updateVersionDir, $updatePath, true);
        
        return true;
    }
    
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
