<?php
/**
 * GetBlocksRessourceAction
 * @package modules.uixul.actions
 */
class uixul_GetBlocksRessourceAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$category = $request->getParameter('category');
		$dashboardBlock = $category == 'dashboard';
		$allowLayout = $request->getParameter('allowLayout') == 'true';
		$ls  = LocaleService::getInstance();
		$bs = block_BlockService::getInstance();
		$sections = array();
		$modules = ModuleService::getInstance()->getModulesObj();
		$blocksDocumentModels = array();
		
		$moduleIcon = MediaHelper::getIcon($category, MediaHelper::SMALL);
		$sections['top'] = array('label' => 'Top', 'icon' => $moduleIcon, 'blocks' => array(), 'open' => true);
		
		if ($allowLayout)
		{
			$sections['top']['blocks']['layout'] = $this->buildLayoutBlocInfoArray();
		}
		if (!$dashboardBlock)
		{
			$sections['top']['blocks']['richtext'] = $this->buildRichtextBlocInfoArray();
			$blocksDocumentModels = $bs->getBlocksDocumentModelToInsert();
		}
		else
		{
			$blocksDocumentModels = array();
		}
		
		foreach ($bs->getBlocksToInsert() as $blockType) 
		{
			$blockInfo = $bs->getBlockInfo($blockType);
			if ($blockInfo->getDashboard() != $dashboardBlock) {continue;}
			$section = $blockInfo->getSection();
			
			$cModule = isset($modules[$section]) ? $modules[$section] : null;		
			if (!isset($sections[$section]))
			{
				if ($cModule !== null)
				{
					$label = $ls->transBO('m.' . strtolower($section) . '.bo.general.module-name', array('ucf'));
					$moduleIcon = MediaHelper::getIcon($modules[$section]->getIconName(), MediaHelper::SMALL);
				}
				else
				{
					$moduleIcon = $sections['top']['icon'];
					$label = ucfirst($section);
				}
				$sections[$section] =  array('label' => $label, 'icon' => $moduleIcon, 'blocks' => array());
			}
			
			$sections[$section]['blocks'][$blockInfo->getType()] = $this->buildBlocInfoArray($blockInfo, $allowLayout);	
		}
			
		foreach ($sections as $sectionName => $data) 
		{
			$blocks = $data['blocks'];
			uasort($blocks, array($this, 'cmpSection'));
			$sections[$sectionName]['blocks'] = array_chunk($blocks, 3, true);
		}	
		
		foreach ($blocksDocumentModels as $modelName => $types)
		{
			list ($package, $document) = explode('/', $modelName);
			list (, $moduleName) = explode('_', $package);
			if (!isset($sections[$moduleName]))
			{
				$cModule = $modules[$moduleName];		
				$label = $ls->transBO('m.' . strtolower($moduleName) . '.bo.general.module-name', array('ucf'));
				$moduleIcon = MediaHelper::getIcon($modules[$moduleName]->getIconName(), MediaHelper::SMALL);
				$sections[$moduleName] =  array('label' => $label, 'icon' => $moduleIcon);
			}		
			if (!isset($sections[$moduleName]['documents']))
			{
				$tree = $this->getDatasources($moduleName, $blocksDocumentModels);
				if ($tree != null)
				{
					$sections[$moduleName]['documents'] = $tree;
				}
			}
		}
		
		uasort($sections, array($this, 'cmpSection'));
		
		return $this->sendJSON($sections);
	}
	
	function cmpSection($a, $b)
	{
	    if ($a['label'] == $b['label']) 
	    {
	        return 0;
	    } 
	    else if ($a['label'] === 'Top')
	    {
	    	 return -1;
	    }
		else if ($b['label'] === 'Top')
	    {
	    	 return 1;
	    }
	    return ($a['label'] < $b['label']) ? -1 : 1;
	}
	/**
	 * @param block_BlockInfo $blockInfo
	 * @return array
	 */
	private function buildBlocInfoArray($blockInfo)
	{
		$jsonInfo = array();
		$jsonInfo['type'] = $blockInfo->getType();
		foreach ($blockInfo->getAttributes() as $name => $value)
		{
			if (strpos($name, '__') === 0 && f_util_StringUtils::isNotEmpty($value))
			{
				$jsonInfo[$name] = $value;
			}
		}
		
		$blockIcon = $blockInfo->getIcon();
		if (empty($blockIcon))
		{
			$blockIcon = 'block';
		}
		$blockIcon = MediaHelper::getIcon($blockIcon, MediaHelper::SMALL);
		
		$result = array();
		$result['icon'] = $blockIcon;
		$result['label'] = $blockInfo->getLabel();		
		$result['type'] = $jsonInfo['type'];
		$result['jsonInfo'] = f_util_StringUtils::JSONEncode($jsonInfo);
				
		return $result;
	}
	
	private function buildLayoutBlocInfoArray()
	{
		$label = LocaleService::getInstance()->transBO('m.website.bo.blocks.two-col', array('ucf'));
		$blockIcon = MediaHelper::getIcon('layout-2-columns', MediaHelper::SMALL);
		$result = array('type' => 'layout', 'label' => $label, 'icon' => $blockIcon);
		$jsonInfo = array();
		$jsonInfo['type'] = 'layout';
		$jsonInfo['columns'] = 2;				
		$result['jsonInfo'] = JsonService::getInstance()->encode($jsonInfo);
		return $result;		
	}

	private function buildRichtextBlocInfoArray()
	{
		$label = LocaleService::getInstance()->transBO('m.uixul.bo.layout.richtextblock', array('ucf'));
		$blockIcon = MediaHelper::getIcon('richtext', MediaHelper::SMALL);
		$result = array('type' => 'richtext', 'label' => $label, 'icon' => $blockIcon);
		$jsonInfo = array();
		$jsonInfo['type'] = 'richtext';		
		$result['jsonInfo'] = JsonService::getInstance()->encode($jsonInfo);
		return $result;		
	}
	
	private function getDatasources($moduleName, $blocksDocumentModels)
	{
		if (!ModuleService::getInstance()->getModule($moduleName)->isVisible())
		{
			return null;
		}
		
		$rootFolderId = ModuleService::getInstance()->getRootFolderId($moduleName);	
		$ps = f_permission_PermissionService::getInstance();
		if (!$ps->hasPermission(users_UserService::getInstance()->getCurrentBackEndUser(), 'modules_' . $moduleName  . '.List.rootfolder', $rootFolderId))
		{
			return null;
		}
		
		$result = array();
		if (uixul_ModuleBindingService::getInstance()->hasConfigFile($moduleName))
		{
			$config = uixul_ModuleBindingService::getInstance()->loadConfig($moduleName);
			$treecomponents = array();
			$listcomponents = array();
			
			foreach ($config['models'] as $name => $modelInfo) 
			{
				if (isset($modelInfo['children']))
				{		
					$result['models'][$name] =  $modelInfo['children'];
				}
				
				if (isset($blocksDocumentModels[$name]))
				{
					$listcomponents[] = $name;
					if (!isset($result['models'][$name]))
					{
						$result['models'][$name] =  true;
					}
				}
			}

			$models = $result['models'];		
			foreach ($models as $name => $value) 
			{
				if (!$this->isInList($name, $listcomponents, $models))
				{
					unset($result['models'][$name]);
				}
				else if (is_array($value))
				{
					$children = array();
					foreach ($value as $submodelName => $data) 
					{
						if ($this->isInList($submodelName, $listcomponents, $models))
						{
							$children[$submodelName] = $data;
						}
					}
					if (count($children) == 0)
					{
						$result['models'][$name] = true;
					}
					else
					{
						$result['models'][$name] = $children;
						$treecomponents[] = $name;
					}
				}	
			}
			
			$result['treecomponents'] = implode(',', $treecomponents);
			$result['listcomponents'] = implode(',', $listcomponents);
		}
		
		if (count($listcomponents) > 0)
		{
			$result['module'] = $moduleName;
			$result['rootFolderId'] = $rootFolderId;
			return $result;

		}
		return null;
	}
	
	private function isInList($modelName, $listcomponents, $modelsList, $pm = array())
	{
		if (!isset($modelsList[$modelName]))
		{
			return false;
		}
		$subModels = $modelsList[$modelName];
		if (is_array($subModels))
		{
			$pm[$modelName] = true;
			foreach ($subModels as $subModelName => $data) 
			{
				if (!isset($pm[$subModelName]) && $this->isInList($subModelName, $listcomponents, $modelsList, $pm))
				{
					return true;
				}
			}
		}
		return in_array($modelName, $listcomponents);
	}
}