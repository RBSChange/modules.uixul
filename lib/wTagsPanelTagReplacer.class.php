<?php
class uixul_lib_wTagsPanelTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$options = array();
		$panels = array();
		$panelsContents = array();
		$moduleLabels = array();
		
		$tagOptionsDoc = new DOMDocument('1.0', 'utf-8');
		$tagOptionsRoot = $tagOptionsDoc->createElement('menupopup');
		$tagOptionsDoc->appendChild($tagOptionsRoot);
		
		$tagPanelsDoc = new DOMDocument('1.0', 'utf-8');
		$tagPanelsRoot = $tagPanelsDoc->createElement('deck');
		$tagPanelsRoot->setAttribute('anonid', 'deck');
		$tagPanelsDoc->appendChild($tagPanelsRoot);
		
		$tags = TagService::getInstance()->getAllAvailableTags();
		foreach ($tags as $tagInfo)
		{
			$package = $tagInfo['package'];
			$contentType = $tagInfo['component_type'];	
			list(, $moduleName) = explode('_', $package);
			$moduleLabel = f_Locale::translate('&modules.' . $moduleName . '.bo.general.Module-name;');
			if (!isset($options[$moduleLabel]))
			{
				$icon = MediaHelper::getIcon(constant('MOD_' . strtoupper($moduleName) . '_ICON'), MediaHelper::SMALL);
				$options[$moduleLabel] = array('icon' => $icon, 'package' => $package, 'label' => $moduleLabel);
				$moduleLabels[$package] = $moduleLabel;
				$panelsContents[$package] = array();
			}
			
			if (!isset($panelsContents[$package][$contentType]))
			{
				$panelsContents[$package][$contentType] = array();
			}
			
			$icon = MediaHelper::getIcon($tagInfo['icon'], MediaHelper::SMALL);
			$label = f_Locale::translate($tagInfo['label']);
			$panelsContents[$package][$contentType][$label] = array('tag-type' => $this->getTagType($tagInfo['tag']), 'tag' => $tagInfo['tag'], 'label' => $label, 'icon' => $icon);
		}
		
		foreach ($panelsContents as $package => $panelContents)
		{
			$panelNode = $this->createPanelNode($tagPanelsDoc, array('package' => $package));
			foreach ($panelContents as $contentType => $contentsByContentType)
			{
				$gridNode = $this->createGridNode($tagPanelsDoc, array('content-type' => $contentType, 'tags-count' => count($contentsByContentType)));
				$panelNode->appendChild($gridNode);
				$rowsNode = $this->initGridNode($tagPanelsDoc, $gridNode);
				
				ksort($contentsByContentType);
				$i = 0;
				foreach ($contentsByContentType as $tagInfo)
				{
					if ($i%3 == 0)
					{
						$rowNode = $tagPanelsDoc->createElement('row');
						$rowsNode->appendChild($rowNode);
					}
					$rowNode->appendChild($this->createTagNode($tagPanelsDoc, $tagInfo));
					$i++;
				}
			}
			$panels[$moduleLabels[$package]] = $panelNode;
		}
		
		ksort($options);
		foreach ($options as $option)
		{
			$tagOptionsRoot->appendChild($this->createOptionNode($tagOptionsDoc, $option));
		}
		$this->setReplacement('TAGS_OPTIONS', str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $tagOptionsDoc->saveXML()));
		
		ksort($panels);
		foreach ($panels as $panel)
		{
			$tagPanelsRoot->appendChild($panel);
		}
		$this->setReplacement('TAGS_PANELS', str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $tagPanelsDoc->saveXML()));
	}
	
	/**
	 * @param DOMDocuent $document
	 * @param Array<String => String> $info
	 * @return DOMNode
	 */
	private function createOptionNode($document, $info)
	{
		$node = $document->createElement('menuitem');
		$node->setAttribute('image', $info['icon']);
		$node->setAttribute('class', 'menuitem-iconic');
		$node->setAttribute('value', $info['package']);
		$node->setAttribute('label', $info['label']);
		$node->setAttribute('short-label', $info['label']);
		$node->setAttribute('anonid', 'tag_option_' . $info['package']);
		$node->setAttribute('package', $info['package']);
		return $node;
	}
	
	/**
	 * @param DOMDocuent $document
	 * @param Array<String => String> $info
	 * @return DOMNode
	 */
	private function createTagNode($document, $info)
	{
		$node = $document->createElement('toolbarbutton');
		$node->setAttribute('image', $info['icon']);
		$node->setAttribute('value', $info['tag']);
		$node->setAttribute('label', $info['label']);
		$node->setAttribute('tooltiptext', $info['tag']);
		$node->setAttribute('anonid', 'tag_' . $info['tag']);
		$node->setAttribute('oncommand', 'addOrRemoveTag(this)');
		$node->setAttribute('tag-type', $info['tag-type']);
		
		$observesNode = $document->createElement('observes');
		$observesNode->setAttribute('element', 'wcontroller');
		$observesNode->setAttribute('attribute', 'disabled');
		$node->appendChild($observesNode);
		
		$box = $document->createElement('hbox');
		$box->appendChild($node);
		return $box;
	}

	/**
	 * @param DOMDocuent $document
	 * @param Array<String => String> $info
	 * @return DOMNode
	 */
	private function createPanelNode($document, $info)
	{
		$node = $document->createElement('vbox');
		$node->setAttribute('flex', '1');
		$node->setAttribute('class', 'tag_panel');
		$node->setAttribute('package', $info['package']);
		$node->setAttribute('anonid', 'tags_panel_' . $info['package']);
		return $node;
	}
	
	/**
	 * @param DOMDocuent $document
	 * @param Array<String => String> $info
	 * @return DOMNode
	 */
	private function createGridNode($document, $info)
	{
		$node = $document->createElement('grid');
		$node->setAttribute('flex', '1');
		$node->setAttribute('content-type', $info['content-type']);
		$node->setAttribute('tags-count', $info['tags-count']);
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
		
		$columnNode = $document->createElement('column');
		$columnNode->setAttribute('flex', '1');
		$columnsNode->appendChild($columnNode);
		$columnNode = $document->createElement('column');
		$columnNode->setAttribute('flex', '1');
		$columnsNode->appendChild($columnNode);
		$columnNode = $document->createElement('column');
		$columnNode->setAttribute('flex', '1');
		$columnsNode->appendChild($columnNode);
		
		$rowsNode = $document->createElement('rows');
		$gridNode->appendChild($rowsNode);
		return $rowsNode;
	}
	
	/**
	 * @param Strung $tag
	 * @return String
	 */
	private function getTagType($tag)
	{
		$ts = TagService::getInstance();
		if ($ts->isDetailPageTag($tag))
		{
			return 'exclusive';
		}
		else if ($ts->isContextualTag($tag))
		{
			return 'contextual';
		}
		else if ($ts->isFunctionalTag($tag))
		{
			return 'functionnal';
		}
		else
		{
			return 'simple';
		}
	}
}