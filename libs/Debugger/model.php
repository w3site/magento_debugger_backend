<?php
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 *
 * @author Tereta Alexander (www.w3site.org)
 */

chdir(MAGENTO_DEBUGGER_PROJECT_DIR);

require MAGENTO_DEBUGGER_DIR . '/libs/Mage.php';

Mage::app('admin')->setUseSessionInUrl(false);

umask(0);

try {
    while(true){
        if (!$modelMethodName){
            Mage::log('Model and method does not present.');
            break;
        }
        
        Mage::log("Starting model \"" . $modelMethodName . "\"");
        $modelActionName = explode('::', $modelMethodName);
        if (count($modelActionName) != 2){
            Mage::log("Please enter a valid method & model (\"namespace_model::method\").");
            break;
        }
        
        $modelMethod = $modelActionName[1];
        $model = @Mage::getModel($modelActionName[0]);
        if (!$model){
            Mage::log("Present model does not exists.");
            break;
        }
        
        if (!method_exists($model, $modelMethod)){
            Mage::log("Present method does not exists.");
            break;
        }
        
        $model->$modelMethod();
        Mage::log("Method has been sucefully processed.");
        break;
    }
} catch (Exception $e) {
    Mage::printException($e);
    exit(1);
}
