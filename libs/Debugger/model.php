<?php
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 *
 * @author Tereta Alexander (www.w3site.org)
 */

try {
    while(true){
        if (!$modelMethodName){
            MagentoDebugger::log("Model and method does not present.");
            break;
        }
        
        MagentoDebugger::log("Starting model \"" . $modelMethodName . "\"");
        $modelActionName = explode('::', $modelMethodName);
        if (count($modelActionName) != 2){
            Mage::log("Please enter a valid method & model (\"namespace_model::method\").");
            break;
        }
        
        $modelMethod = $modelActionName[1];
        $model = Mage::getModel($modelActionName[0]);
        if (!$model){
            MagentoDebugger::log("Present model does not exists.");
            break;
        }
        
        if (!method_exists($model, $modelMethod)){
            MagentoDebugger::log("Present method does not exists.");
            break;
        }
        
        $model->$modelMethod();
        MagentoDebugger::log();
        MagentoDebugger::log("Method has been sucefully processed.");
        break;
    }
} catch (Exception $e) {
    Mage::printException($e);
    exit(1);
}
