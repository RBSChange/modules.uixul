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
		$allowLayout = $request->getParameter('allowLayout') == 'true';
		
		$sections = array();
		$modules = array();
		$availableModules = ModuleService::getInstance()->getModules();
		$ls  = LocaleService::getInstance();
		foreach ($availableModules as $availableModuleName)
		{
			$availableShortModuleName = substr($availableModuleName, strpos($availableModuleName, '_') + 1);
			if (defined('MOD_' . strtoupper($availableShortModuleName) . '_ENABLED') && (constant('MOD_' . strtoupper($availableShortModuleName) . '_ENABLED') == true))
			{
				$modules[] = $availableShortModuleName;
				$sections[$availableShortModuleName] = $ls->transBO('m.' . strtolower($availableShortModuleName) . '.bo.general.module-name', array('ucf', 'space'));
			}
		}
		
		asort($sections, SORT_STRING);
		if (isset($sections[$category]))
		{
			$label = $sections[$category];
			unset($sections[$category]);
			$iconName = constant('MOD_' . strtoupper($category) . '_ICON');
			$popularSection = array('label' => $label, 'open' => true, 'icon' =>  MediaHelper::getIcon($iconName, MediaHelper::SMALL));
			$sections = array_merge(array($category => $popularSection), $sections);
		}
		
		$bs = block_BlockService::getInstance();
			
			// Module blocks :
		foreach ($modules as $module)
		{
			$declaredModuleBlocks = $bs->getDeclaredBlocksForModule($module);
			if (count($declaredModuleBlocks) > 0)
			{
				$hiddenBlocks = array();
				$visibleBlocks = array();
				
				foreach ($declaredModuleBlocks as $blockName)
				{
					$blockInfo = $bs->getBlockInfo($blockName, 'modules_' . $module);
					if ($blockInfo->hasAttribute('deprecated'))
					{
						continue;
					}
					if (!$allowLayout && $blockInfo->getType() === 'layout')
					{
						continue;
					}
					if ($category === 'website' && $blockInfo->getDashboard())
					{
						continue;
					}
					
					if ($category === 'dashboard' && !$blockInfo->getDashboard())   
					{
						continue;						
					}
					
					if ($blockInfo->isHidden())
					{
						$hiddenBlocks[$blockName] = $blockInfo;
					} 
					else
					{
						$visibleBlocks[$blockName] = $blockInfo;
					}
				}
				
				foreach ($visibleBlocks as $blockName => $blockInfo)
				{
					$visible = true;	
					foreach ($hiddenBlocks as $hiddenBlockInfo)
					{						
						if (($blockInfo->getType() == $hiddenBlockInfo->getType()) 
							&& ($blockInfo->getLabel() == $hiddenBlockInfo->getLabel()) 
							&& ($blockInfo->getIcon() == $hiddenBlockInfo->getIcon()))
						{
							$visible = false;
							break;
						}
					}
					if (!$visible) {continue;}
					
					if (is_string($sections[$module]))
					{
						$moduleIconName = defined('MOD_' . strtoupper($module) . '_ICON') ? constant('MOD_' . strtoupper($module) . '_ICON') : 'component';
						$moduleIcon = MediaHelper::getIcon($moduleIconName, MediaHelper::SMALL);
						$sections[$module] = array('label' => $sections[$module], 'icon' => $moduleIcon, 'blocks' => array());
					}
					$sections[$module]['blocks'][$blockName] = $this->buildBlocInfoArray($blockInfo, $allowLayout);
				}
			}
			
			if ($category === 'website')
			{
				$tree = $this->getDatasources($module);
				if ($tree != null)
				{
					if (is_string($sections[$module]))
					{
						$moduleIconName = defined('MOD_' . strtoupper($module) . '_ICON') ? constant('MOD_' . strtoupper($module) . '_ICON') : 'component';
						$moduleIcon = MediaHelper::getIcon($moduleIconName, MediaHelper::SMALL);
						$sections[$module] = array('label' => $sections[$module], 'icon' => $moduleIcon);
					}
					$sections[$module]['documents'] = $tree;
				}
			}
		}
		
		foreach ($sections as $module => $data) 
		{
			if (!is_array($data))
			{
				unset($sections[$module]);
			}
			else
			{
				if (isset($data['blocks']))
				{
					$sections[$module]['blocks'] = array_chunk($data['blocks'], 3, true);
				}
			}
		}
		return $this->sendJSON($sections);
	}
	
	
	/**
	 * @param block_BlockInfo $blockInfo
	 * @return array
	 */
	private function buildBlocInfoArray($blockInfo)
	{
		$jsonInfo = array();
		$jsonInfo['isContent'] = $blockInfo->isContent();
		if ($jsonInfo['isContent'])
		{
			$jsonInfo['data'] = $blockInfo->getContent();
		} 
		else if ($blockInfo->hasContent())
		{
			$jsonInfo['content'] = $blockInfo->getContent();
		}	
		
		if (f_util_StringUtils::isNotEmpty($blockInfo->getRef()))
		{
			$jsonInfo['ref'] =  $blockInfo->getRef();
		}
		
		$jsonInfo['type'] = $blockInfo->getType();
		foreach ($blockInfo->getAttributes() as $name => $value)
		{
			if (f_util_StringUtils::isNotEmpty($value))
			{
				$jsonInfo[$name] = $value;
			}
		}					
		$blockLabel = $blockInfo->getLabel();
		if (!$blockLabel)
		{
			$blockLabel = "&modules.uixul.layout.UnknownBlock;";
		}		
		$blockIcon = $blockInfo->getIcon();
		if (!$blockIcon)
		{
			$blockIcon = "cubes";
		}
		$blockIcon = MediaHelper::getIcon($blockIcon, MediaHelper::SMALL);
		
		$result = array();
		$result['icon'] = $blockIcon;
		$result['label'] = f_Locale::translateUI($blockLabel);
		$result['type'] = $jsonInfo['type'];
		$result['jsonInfo'] = f_util_StringUtils::JSONEncode($jsonInfo);
				
		return $result;
	}
		
	private function getDatasources($moduleName)
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
				$listcomponents[] = $name;
				if (isset($modelInfo['children']))
				{		
					$result['models'][$name] =  $modelInfo['children'];
					$treecomponents[] = $name;
				}
				else
				{
					$result['models'][$name] =  true;
				}
			}
			$result['treecomponents'] = implode(',', $treecomponents);
			$result['listcomponents'] = implode(',', $listcomponents);
		}
		else
		{
			
			$file = FileResolver::getInstance()->setPackageName('modules_'.$moduleName)
				->setDirectory('config')->getPath('datasources.xml');
				
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
					$attributes = array('treecomponents', 'listcomponents');
					foreach ($attributes as $attribute)
					{
						if ($datasourceElm->hasAttribute($attribute))
						{
							$originalModelNames = explode(',', $datasourceElm->getAttribute($attribute));
							$modelNames = $originalModelNames;
							$result[$attribute] = implode(',', $modelNames);
						}
					}
				}
			}
		}

		if (count($result) > 0 && isset($result['listcomponents']))
		{
			$types = explode(',', $result['listcomponents']);
			$folderOnly = true;
			foreach ($types as $type) 
			{
				if (strpos($type, 'folder') !== false)
				{
					$folderOnly = false;
					break;
				}
			}
			if (!$folderOnly)
			{
				$result['module'] = $moduleName;
				$result['rootFolderId'] = $rootFolderId;
				return $result;
			}
		}
		return null;
	}
}