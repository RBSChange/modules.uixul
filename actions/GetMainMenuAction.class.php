<?php

class uixul_GetMainMenuAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$rc = RequestContext::getInstance();
		$rc->setUILangFromParameter($request->getParameter('uilang'));
		
		try 
		{
			$rc->beginI18nWork($rc->getUILang());
			$modules = array();
			foreach (ModuleService::getInstance()->getModulesObj() as $moduleObj)
			{
				$desc = $this->getModuleDescriptor($moduleObj);
				$modules[$desc['label']] = $desc;
			}
			ksort($modules);
			$langs = array();
			
			$defaultLang = $rc->getDefaultLang();
			foreach ($rc->getSupportedLanguages() as $lang)
			{
				$langs[] = array('value' => $lang, 'label' => LocaleService::getInstance()->trans('m.uixul.bo.languages.' . strtolower($lang), array('ucf')), 'default' => $defaultLang == $lang);
			}	
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		return $this->sendJSON(array('modules' => array_values($modules), 'langs' => $langs, 'version' => Framework::getVersion()));
	}
	
	/**
	 * @param c_Module $moduleObj
	 * @return array
	 */
	private function getModuleDescriptor($moduleObj)
	{
		$ms = ModuleService::getInstance();
		$moduleName = $moduleObj->getName();
		$upperModuleName = strtoupper($moduleName);
		$icon = MediaHelper::getIcon($moduleObj->getIconName());
		$smallIcon = MediaHelper::getIcon($moduleObj->getIconName(), MediaHelper::SMALL);
		$category = "base-modules";
		if ($moduleObj->getCategory() != 'modules')
		{
			$category = $moduleObj->getCategory();
		}
		$visible = $moduleObj->isVisible();
		return array('name' => $moduleName, 'label' => $ms->getUILocalizedModuleLabel($moduleName), 'icon' => $icon, 'small-icon' => $smallIcon, 'category' => $category, 'visible' => $visible);
	}


}