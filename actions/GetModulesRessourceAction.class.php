<?php
/**
 * GetModulesRessourceAction
 * @package modules.uixul.actions
 */
class uixul_GetModulesRessourceAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();	
		$linkedModuleArray = ModuleService::getInstance()->getPackageNames();
		foreach ($linkedModuleArray as $linkedModuleName)
		{

			$linkedModuleName = substr($linkedModuleName, 8);
			$ds = $this->getDatasources($linkedModuleName);
			if ($ds)
			{
				$pushedDsContent[$ds['label']] = $ds;
			}
		}
		ksort($pushedDsContent, SORT_STRING);
		foreach ($pushedDsContent as $ds)
		{
			$result[$ds['module']] = $ds;
		}
		return $this->sendJSON($result);
	}
		
	/**
	 * @param c_Module $cModule
	 * @param integer $rootFolderId
	 * @return boolean
	 */
	private function canDisplayModuleAsDatasource($cModule, $rootFolderId)
	{
		if (!$cModule->isVisible())
		{
			return false;
		}
		$ps = change_PermissionService::getInstance();
		return $ps->hasPermission(users_UserService::getInstance()->getCurrentBackEndUser(), 'modules_' . $cModule->getName() . '.List.rootfolder', $rootFolderId);
	}	
	
	private function getDatasources($moduleName)
	{
		$cModule = ModuleService::getInstance()->getModule($moduleName);
		$result = array();
		$rootFolderId = $cModule->getRootFolderId();
		if (!$this->canDisplayModuleAsDatasource($cModule, $rootFolderId) || !$cModule->hasPerspectiveConfigFile())
		{
			return null;
		}

		$config = uixul_ModuleBindingService::getInstance()->loadConfig($moduleName);
		$treecomponents = array();
		$listcomponents = array();
		
		foreach ($config['models'] as $name => $modelInfo) 
		{
			if (isset($modelInfo['children']))
			{		
				$result['models'][$name] =  $modelInfo['children'];
				$treecomponents[] = $name;
			}
			else
			{
				$result['models'][$name] =  true;
			}
			$listcomponents[] = $name;
		}
		$result['treecomponents'] = implode(',', $treecomponents);
		$result['listcomponents'] = implode(',', $listcomponents);
		$result['label'] = $cModule->getUILabel();
		$result['icon'] = MediaHelper::getIcon($cModule->getIconName(), MediaHelper::SMALL);

		if (count($result) > 0)
		{
			$result['module'] = $moduleName;
			$result['rootFolderId'] = $rootFolderId;
			return $result;
		}
		return null;
	}
	
	/**
	 * @see f_action_BaseAction::isSecure()
	 * @return boolean
	 */
	public function isSecure()
	{
		return true;
	}
}