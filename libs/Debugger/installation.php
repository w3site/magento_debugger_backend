<?php 
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 * 
 * @author Tereta Alexander (www.w3site.org)
 */
class MagentoDebugger_Installation{
    protected $_currentHost = null;
    protected $_postIdentifier = null;
    protected $_postProjectDirectory = null;
    protected $_errors = array();
    protected $_messages = array();
    
    public function __construct($currentHost){
        $this->_currentHost = $currentHost;
    }
    
    public function getIdentifier(){
        if ($this->_postIdentifier){
            return $this->_postIdentifier;
        }
        elseif (!$this->_currentHost){
            $serverName = $_SERVER['SERVER_NAME'];
            return str_replace('.', '_', $serverName);
        }
        elseif ($this->_currentHost){
            return $this->_currentHost['identifier'];
        }
    }
    
    public function getProjectDirectory(){
        if ($this->_postProjectDirectory){
            return $this->_postProjectDirectory;
        }
        elseif (!$this->_currentHost){
            return;
        }
        elseif ($this->_currentHost){
            return $this->_currentHost['dir'];
        }
    }
    
    public function getMessages(){
        return $this->_messages;
    }
    
    public function getErrors(){
        return $this->_errors;
    }
    
    protected function _stripData($data){
        $data = strip_tags($data);
        $data = stripslashes($data);
        return $data;
    }
    
    public function savePost(){
        if (!$_POST){
            return;
        }
        
        $identifier = null;
        $projectDirectory = null;
        
        if (isset($_POST['project_identifier'])){
            $identifier = $this->_postIdentifier = $this->_stripData($_POST['project_identifier']);
        }
        
        if (isset($_POST['project_directory'])){
            $projectDirectory = $this->_postProjectDirectory = $this->_stripData(MagentoDebugger::getPath($_POST['project_directory']));
        }
        
        if (!$this->verifyMagentoDirectory($projectDirectory)){
            array_push($this->_errors, 'Please enter a right magento project directory.');
            return;
        }
        
        $dataToSave = "[config]\n";
        $dataToSave .= "name = '" . $_SERVER['SERVER_NAME'] . "'\n";
        $dataToSave .= "dir = '" . $projectDirectory . "'\n";
        
        $configDir = MagentoDebugger::getDebuggerDir() . '/config';
        $varDir = MagentoDebugger::getDebuggerDir() . '/var';
        
        $allowed = true;
        
        if (!MagentoDebugger::isWritable($varDir)){
            array_push($this->_errors, 'Please make "var" dir at the Magento Debugger and all files on it writable ("' . $varDir . '").');
            $allowed = false;
        }
        
        if (!MagentoDebugger::isWritable($configDir)){
            array_push($this->_errors, 'Please make "config" dir at the Magento Debugger and all files on it writable ("' . $configDir . '").');
            $allowed = false;
        }
        
        if ($allowed && @file_put_contents($configDir . '/' . $identifier . '.ini', $dataToSave)){
            array_push($this->_messages, 'Host sucefully configured.');
        }
        elseif ($allowed){
            array_push($this->_errors, 'Please check the dir "config" on the Magento Debugger. The file ' . $configDir . '/' . $identifier . '.ini can not be written.');
        }
    }
    
    public function verifyMagentoDirectory($dir){
        if (!is_dir($dir)){
            return false;
        }
        
        if (!is_file($dir . '/index.php')){
            return false;
        }
        
        if (!is_file($dir . '/app/Mage.php')){
            return false;
        }
        
        return true;
    }
}

$installation = new MagentoDebugger_Installation($currentHost);
$installation->savePost();
?>
<html>
    <head>
        <link rel="stylesheet" href="/?magento_debug=file&magento_debug_file=css/style.css" />
    </head>
    <body>
        <header>
            <img src="/?magento_debug=file&magento_debug_file=images/icon.png" class="logo" />
            <h1 id="heading">Magento Debugger</h1>
            <a class="heading-link" href="http://w3site.org">W3Site.org</a>
        </header>
        
        <h1>Configuration for the host "<?php echo $_SERVER['SERVER_NAME'] ?>"</h1>
        <?php if ($installation->getMessages()) : ?>
            <?php foreach($installation->getMessages() as $message) : ?>
                <p class="message"><?php echo $message ?></p>
            <?php endforeach ?>
        <?php endif ?>
        <?php if ($installation->getErrors()) : ?>
            <?php foreach($installation->getErrors() as $error) : ?>
                <p class="error"><?php echo $error ?></p>
            <?php endforeach ?>
        <?php endif ?>
        
        <form method="post" action="/?magento_debug=configure">
            <fieldset>
                <legend>Project</legend>
                <table>
                    <tr>
                        <th>
                            Identifier
                        </th>
                        <td>
                            <input type="text" name="project_identifier" value="<?php echo addslashes($installation->getIdentifier()) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Directory
                        </th>
                        <td>
                            <input type="text" name="project_directory" value="<?php echo addslashes($installation->getProjectDirectory()) ?>" />
                        </td>
                    </tr>
                </table>
                <input type="submit" value="Save" /> or <a href="/">proceed to the project</a>
            </fieldset>
        </form>
    </body>
</html>