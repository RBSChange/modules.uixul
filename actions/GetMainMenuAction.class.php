<?php

class uixul_GetMainMenuAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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
				$langs[] = array('value' => $lang, 'label' => f_Locale::translateUI('&modules.uixul.bo.languages.' . ucfirst($lang) . ';'), 'default' => $defaultLang == $lang);
			}	

			$portals = $this->getPortalInfos();
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		
		$phase = Framework::getConfiguration('general/phase');
		$phaseLabel = LocaleService::getInstance()->transBO('m.generic.bo.general.phase-' . $phase);
		
		return $this->sendJSON(array('modules' => array_values($modules), 'langs' => $langs, 'portals' => $portals, 'version' => Framework::getVersion(), 'phase' => $phase, 'phaseLabel' => $phaseLabel));
	}
	
	private function getPortalInfos()
	{
		$portals = null;
		$portalInfos = Framework::getConfigurationValue('modules/uixul/portal', null);
		if ($portalInfos !== null)
		{
			$user = users_UserService::getInstance()->getCurrentBackEndUser();
			$portals = array('label' => $portalInfos['name'], 'items' => array());
			$listHistory = array();
			$i = 1;
			while (isset($portalInfos['name_' . $i]))
			{
				if (!in_array($portalInfos['name_' . $i], $listHistory) && ($portalInfos['url_' . $i] != Framework::getUIBaseUrl()))
				{
					$command = 'portalredirect(\'' . $portalInfos['url_' . $i] . '\', \'' . htmlentities($user->getLogin()) . '\', \'' . htmlentities($user->getPasswordmd5()) . '\');';
					$portals['items'][] = array('label' => $portalInfos['name_' . $i], 'command' => $command);
					$listHistory[] = $portalInfos['name_' . $i];
				}
				$i++;
			}
		}
		return $portals;
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
		if (Framework::inDevelopmentMode() === true)
		{
			$version = $moduleObj->hasPerspectiveConfigFile() ? 'v3' : 'v2';
		}
		else
		{
			$version = "v3";
		}
		return array('name' => $moduleName, 'label' => $ms->getUILocalizedModuleLabel($moduleName), 'icon' => $icon, 'small-icon' => $smallIcon, 'category' => $category, 'visible' => $visible, 'version' => $version);
	}


}