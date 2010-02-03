<?php

class uixul_lib_wBaseModuleTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$actions = array();
		uixul_lib_UiService::getModuleActions('uixul', $actions);
		
    	$actionsToMethodsTransformer = new uixul_ActionsToMethodsTransformer('uixul');
    	$methodArray = array();
    	$initCodeArray = array();
    	
    	// $methodArray and $initCodeArray are passed by reference
    	$actionsToMethodsTransformer->transform($actions, $methodArray, $initCodeArray);
    	
		$this->setReplacement('ACTIONSDEFINITION', implode(";\n", $initCodeArray));	
		$this->setReplacement('ACTIONSIMPLEMENTATION', implode("\n", $methodArray));
	}
}