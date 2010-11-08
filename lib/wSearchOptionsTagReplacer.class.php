<?php
class uixul_lib_wSearchOptionsTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$availableModules = ModuleService::getInstance()->getModules();
		
		$menuItems = array();
		$moduleList = array();
		foreach ($availableModules as $availableModuleName)
		{
			$moduleName = substr($availableModuleName, strpos($availableModuleName, '_') + 1);
			$visibilityConstantName = 'MOD_' . strtoupper($moduleName) . '_VISIBLE';
			if (defined($visibilityConstantName) && (constant($visibilityConstantName) == true))
			{
				if ($moduleName === 'dashboard')
				{
					continue;
				}
				$localKey = '&' . str_replace('_', '.', $availableModuleName) . '.bo.general.Module-name;';
				$moduleList[f_Locale::translate($localKey)] = $moduleName;
			}
		}
		
		ksort($moduleList);
		
		foreach ($moduleList as $moduleLocalizedName => $moduleName)
		{
			$menuItems[] = '<xul:menuitem label="' . $moduleLocalizedName . '" value="' . $moduleName . '" />';
		}
		$this->setReplacement('MODULESLIST', implode(K::CRLF, $menuItems));
	}
}