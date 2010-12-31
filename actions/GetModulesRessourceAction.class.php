<?php
/**
 * GetModulesRessourceAction
 * @package modules.uixul.actions
 */
class uixul_GetModulesRessourceAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();	
		$linkedModuleArray = ModuleService::getInstance()->getModules();
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
	 * @param Integer $rootFolderId
	 * @return Boolean
	 */
	private function canDisplayModuleAsDatasource($cModule, $rootFolderId)
	{
		if (!$cModule->isVisible())
		{
			return false;
		}
		$ps = f_permission_PermissionService::getInstance();
		return $ps->hasPermission(users_UserService::getInstance()->getCurrentBackEndUser(), 'modules_' . $cModule->getName() . '.List.rootfolder', $rootFolderId);
	}	
	
	private function getDatasources($moduleName)
	{
		$cModule = ModuleService::getInstance()->getModule($moduleName);
		$result = array();
		$rootFolderId = $cModule->getRootFolderId();
		if (!$this->canDisplayModuleAsDatasource($cModule, $rootFolderId))
		{
			return null;
		}
		
		if ($cModule->hasPerspectiveConfigFile())
		{
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
		}
		else
		{
			
			$file = FileResolver::getInstance()->setPackageName('modules_'.$moduleName)
				->setDirectory('config')
				->getPath('datasources.xml');
				
			if ($file !== null)
			{
				$domDoc = new DOMDocument();
				$domDoc->load($file);
				$xpath = new DOMXPath($domDoc);
				$query = '/datasources/datasource[not(@name)]';
				$nodeList = $xpath->query($query);
				if ($nodeList->length > 0)
				{
					$datasourceElm = $nodeList->item(0);
					
					$attributes = array('treecomponents', 'listcomponents', 'icon', 'listfilter', 'listparser', 'treeparser');
					foreach ($attributes as $attribute)
					{
						if ($datasourceElm->hasAttribute($attribute))
						{
							switch ($attribute)
							{
								case 'treecomponents' :
								case 'listcomponents' :
									$originalModelNames = explode(',', $datasourceElm->getAttribute($attribute));
									$modelNames = $originalModelNames;
									$result[$attribute] = implode(',', $modelNames);
									break;
								default:
									$result[$attribute] = $datasourceElm->getAttribute($attribute);
									break;
							}
						}
						else if ( $attribute == 'icon')
						{
							$result[$attribute] = $cModule->getIconName();
						}
					}
					
					if (isset($result['label']))
					{
						$result['label'] = $cModule->getUILabel(). ' - ' . f_Locale::translateUI($result['label']);
					}
					else
					{
						$result['label'] = $cModule->getUILabel();
					}
					
					$result['icon'] = MediaHelper::getIcon($result['icon'], MediaHelper::SMALL);
				}
			}
		}

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
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return true;
	}

}