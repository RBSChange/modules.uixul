<?php
class uixul_lib_wSearchOptionsTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$availableModules = ModuleService::getInstance()->getModulesObj();
		
		$menuItems = array();
		$moduleList = array();
		foreach ($availableModules as $cModule)
		{
			if ($cModule->isVisible())
			{
				$moduleName = $cModule->getName();
				if ($moduleName === 'dashboard') {continue;}
				$moduleList[$cModule->getUILabel()] = $moduleName;
			}
		}
		
		ksort($moduleList);
		
		foreach ($moduleList as $moduleLocalizedName => $moduleName)
		{
			$menuItems[] = '<xul:menuitem label="' . $moduleLocalizedName . '" value="' . $moduleName . '" />';
		}
		$this->setReplacement('MODULESLIST', implode(PHP_EOL, $menuItems));
	}
}