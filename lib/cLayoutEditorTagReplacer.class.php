<?php
/**
 * uixul_lib_cLayoutEditorTagReplacer
 * @package modules.uixul
 */
class uixul_lib_cLayoutEditorTagReplacer extends f_util_TagReplacer
{
	
	protected function preRun()
	{
		$fileResolver = Resolver::getInstance('file');
		
		$modules = array();
		$availableModules = ModuleService::getInstance()->getModules();
		foreach ($availableModules as $availableModuleName)
		{
			$availableShortModuleName = substr($availableModuleName, strpos($availableModuleName, '_') + 1);
			$modules[] = $availableShortModuleName;
		}
		
		$bs = block_BlockService::getInstance();
		$moduleBlockInfoArray = array();
		
		// Module blocks :
		foreach ($modules as $module)
		{
			if (defined('MOD_' . strtoupper($module) . '_ENABLED') && (constant('MOD_' . strtoupper($module) . '_ENABLED') == true))
			{
				$declaredModuleBlocks = $bs->getDeclaredBlocksForModule($module);
				if (count($declaredModuleBlocks) > 0)
				{
					$moduleIcon = defined('MOD_' . strtoupper($module) . '_ICON') ? constant('MOD_' . strtoupper($module) . '_ICON') : 'component';
					$moduleLabel = f_Locale::translateUI('&modules.' . $module . '.bo.general.Module-NameSpaced;');
					$moduleBlockInfoArray[$moduleLabel] = array('module' => $module, 'label' => $moduleLabel, 'icon' => MediaHelper::getIcon($moduleIcon, MediaHelper::SMALL), 'blocks' => array());
					
					$hiddenBlocks = array();
					$visibleBlocks = array();
					
					// intcours - 1 - take hidden and visible blocks apart :
					foreach ($declaredModuleBlocks as $blockName)
					{
						$blockInfo = $bs->getBlockInfo($blockName, 'modules_' . $module);
						if ($blockInfo->hasAttribute('deprecated'))
						{
							continue;						
						}
						if ($blockInfo->isHidden())
						{
							$hiddenBlocks[$blockName] = $blockInfo;
						} else
						{
							$visibleBlocks[$blockName] = $blockInfo;
						}
					}
					
					// intcours - 2 - a visible block must be skipped if it shares 
					// its main properties (type, label, icon and display for now) with an hidden one :
					foreach ($visibleBlocks as $blockName => $blockInfo)
					{
						$visible = true;	
						foreach ($hiddenBlocks as $hiddenBlockName => $hiddenBlockInfo)
						{						
							if (($blockInfo->getType() == $hiddenBlockInfo->getType()) 
								&& ($blockInfo->getLabel() == $hiddenBlockInfo->getLabel()) 
								&& ($blockInfo->getIcon() == $hiddenBlockInfo->getIcon()))
							{
								$visible = false;
								break;
							}
						}
						
						if ($visible)
						{
							$moduleBlockInfoArray[$moduleLabel]['blocks'][$blockName] = $blockInfo;
						}
					}
				}
			}
		}
		$tabs = array();
		$panels = array();
		
		ksort($moduleBlockInfoArray, SORT_STRING);
		
		$tabIndex = 0;
		foreach ($moduleBlockInfoArray as $moduleLabel => $moduleInfo)
		{
			if ($moduleInfo['module'] == 'website')
			{
				$tabIndex = 1;
				break;
			}
		}
		
		// Iterate over each module.
		foreach ($moduleBlockInfoArray as $moduleLabel => $moduleInfo)
		{
			$panel = $this->generateBlockPanel($moduleInfo);
			if ($panel !== null)
			{
				if ($moduleInfo['module'] == 'website') 
				{
					$actualTabIndex = 0;
				}
				else
				{
					$actualTabIndex = $tabIndex;
					$tabIndex++;
				}
				$menuItem = array();
				$label = htmlspecialchars(f_Locale::translate($moduleInfo['label']), ENT_QUOTES);
				$menuItem[] = '<menuitem class="menuitem-iconic"';
				$menuItem[] = 'label="'.$label.'" wmodule="'.$moduleInfo['module'].'"';
				$menuItem[] = 'anonid="blocksLayoutMenu_'.$actualTabIndex.'"';
				$menuItem[] = 'image="'.f_Locale::translate($moduleInfo['icon']).'"';
				$menuItem[] = 'oncommand="onSelectBlockDeck('.$actualTabIndex.')"';
				$menuItem[] = '/>';
				$tabs[$actualTabIndex] = implode(' ', $menuItem);
				$panels[$actualTabIndex] = $panel;
			}
			$panel = $this->generateBlockPanel($moduleInfo, true);
			if ($panel !== null)
			{
				$actualTabIndex = $tabIndex;
				$tabIndex++;

				$menuItem = array();
				$label = htmlspecialchars(f_Locale::translate($moduleInfo['label']), ENT_QUOTES);
				$menuItem[] = '<menuitem class="menuitem-iconic"';
				$menuItem[] = 'label="'.$label.'" wmodule="'.$moduleInfo['module'].'" dashboard="true"';
				$menuItem[] = 'anonid="blocksLayoutMenu_'.$actualTabIndex.'"';
				$menuItem[] = 'image="'.f_Locale::translate($moduleInfo['icon']).'"';
				$menuItem[] = 'oncommand="onSelectBlockDeck('.$actualTabIndex.')"';
				$menuItem[] = '/>';
				$tabs[$actualTabIndex] = implode(' ', $menuItem);
				$panels[$actualTabIndex] = $panel;
			}
			
		} // end foreach ($moduleBlockInfoArray as $moduleLabel => $moduleInfo)
		
		ksort($tabs);
		ksort($panels);
		$this->setReplacement('BLOCKTABS', implode(K::CRLF, $tabs));
		$this->setReplacement('BLOCKPANELS', implode(K::CRLF, $panels));
	}
	
	const COLUMN_COUNT = 4;
	
	private function generateBlockPanel($moduleInfo, $forDashboard = false)
	{
		$document = new DOMDocument('1.0', 'utf-8');
		$panelRoot = $document->createElement('scrollbox');
		$panelRoot->setAttribute('flex', '1');
		$panelRoot->setAttribute('max-height', '100');
		$document->appendChild($panelRoot);
		
		$gridNode = $document->createElement('grid');
		$gridNode->setAttribute('flex', '1');
		$panelRoot->appendChild($gridNode);
		
		$rowsNode = $this->initGridNode($document, $gridNode);
		
		// Iterate over each blockInfo.
		$i = 0;
		foreach ($moduleInfo['blocks'] as $blockInfo)
		{
			$blockType = $blockInfo->getType();
			if ($blockType && $blockInfo->getDashboard() == $forDashboard)
			{
				if ($i%self::COLUMN_COUNT == 0)
				{
					$rowNode = $document->createElement('row');
					$rowsNode->appendChild($rowNode);
				}
				$rowNode->appendChild($this->createBlockNode($document, $blockInfo));
				$i++;			
			}
		}
		if ($i > 0)
		{
			return str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $document->saveXML());
		}
		return null;
	}
	
	/**
	 * @var users_persistentdocument_backenduser
	 */
	private $user;
	
	/**
	 * @param DOMDocuent $document
	 * @param ... $blockInfo
	 * @return DOMNode
	 */
	private function createBlockNode($document, $blockInfo)
	{
		$dragInfo = array();
		$dragInfo['isContent'] = $blockInfo->isContent();
		if ($dragInfo['isContent'])
		{
			$dragInfo['data'] = $blockInfo->getContent();
		} 
		else if ($blockInfo->hasContent())
		{
			$dragInfo['content'] = $blockInfo->getContent();
		}		
		$dragInfo['ref'] = $blockInfo->getRef();
		$dragInfo['type'] = $blockInfo->getType();
		foreach ($blockInfo->getAttributes() as $name => $value)
		{
			$dragInfo[$name] = $value;
		}					
		
		$blockLabel = $blockInfo->getLabel();
		if (!$blockLabel)
		{
			$blockLabel = f_Locale::translate("&modules.uixul.layout.UnknownBlock;");
		}
		
		$blockIcon = $blockInfo->getIcon();
		if (!$blockIcon)
		{
			$blockIcon = "cubes";
		}
		$blockIcon = MediaHelper::getIcon($blockIcon, MediaHelper::SMALL);
		
		$node = $document->createElement('clayoutblockbutton');
		$node->setAttribute('image', $blockIcon);
		$node->setAttribute('label', $blockLabel);
		$node->setAttribute('dragInfo', f_util_StringUtils::JSONEncode($dragInfo));
		return $node;
	}
	
	/**
	 * @param DOMDocuent $document
	 * @param DOMNode $gridNode
	 * @return DOMNode
	 */
	private function initGridNode($document, $gridNode)
	{
		$columnsNode = $document->createElement('columns');
		$gridNode->appendChild($columnsNode);
		
		for ($i = 0; $i < self::COLUMN_COUNT; $i++)
		{
			$columnNode = $document->createElement('column');
			$columnNode->setAttribute('flex', '1');
			$columnsNode->appendChild($columnNode);
		}
		
		$rowsNode = $document->createElement('rows');
		$gridNode->appendChild($rowsNode);
		return $rowsNode;
	}
}