<?php
/**
 * @package modules.uixul
 * @method uixul_BindingService getInstance() 
 */
class uixul_BindingService extends change_BaseService
{
	/**
	 * @param string $moduleName
	 * @return string
	 */	
	public function getModules($moduleName)
	{
		$result = array();
		$url = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')
				->setQueryParameter('uilang', RequestContext::getInstance()->getUILang())
				->setQueryParameter('wemod', $moduleName)
				->setQueryParameter('binding', 'modules.'.$moduleName)
				->setFragment('wModule-'.$moduleName)
				->getUrl();
		$result[] = 'wmodule[name="'.$moduleName.'"] {-moz-binding: url('.$url.');}';	
		return implode("\n", $result);
	}
	
	/**
	 * @param string $moduleName
	 * @return string
	 */	
	public function getBlocks($moduleName)
	{
		$result = array();
		$blockService = block_BlockService::getInstance();

		// for each block found, create the CSS rule...
		foreach ($this->getBlocksDefinitionForModule($moduleName) as $blockName)
		{
			if ($blockService->isSpecialBlock($blockName))
			{
				continue;
			}
			$url = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')
					->setQueryParameter('uilang', RequestContext::getInstance()->getUILang())
					->setQueryParameter('wemod', $moduleName)
					->setQueryParameter('binding', 'modules.'.$moduleName.'.block.'.$blockName)
					->setFragment('wPropertyGrid')
					->getUrl();
			$result[] = 'wpropertygrid[block="'.$blockName.'"] {-moz-binding: url('.$url.');}';
		}
		return implode("\n", $result);
	}
	
	/**
	 * @param string $moduleName
	 * @param Array<String> Array of defined blocks in the given module.
	 */
	private function getBlocksDefinitionForModule($moduleName)
	{
		$resultBlocks = array();

		$blocksDefinitionFiles = FileResolver::getInstance()->setPackageName('modules_'.$moduleName)->getPaths('config/blocks.xml');
		if (is_array($blocksDefinitionFiles))
		{
			foreach ($blocksDefinitionFiles as $blocksDefinitionFile)
			{
				$domDoc = new DOMDocument();
				$domDoc->preserveWhiteSpace = false;
				if ( ! $domDoc->load($blocksDefinitionFile) )
				{
					throw new Exception("XML not well-formed in \"$blocksDefinitionFile\".");
				}
				$xpath = new DOMXPath($domDoc);
				$blocksNodeList = $xpath->query('/blocks/block');
				for ($i=0 ; $i<$blocksNodeList->length ; $i++)
				{
					$blockElm = $blocksNodeList->item($i);
					$blockName = $blockElm->getAttribute('type');
					$resultBlocks[] = $blockName;
				}
			}
		}
		return array_unique($resultBlocks);
	}
	
	private function LoadWidgetConfig($moduleName, $widgetId)
	{
		$widgetXmlFilePath = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)
							->setDirectory('config/widgets')
							->getPath($widgetId . '.xml');

		if ($widgetXmlFilePath === null)
		{
			if ($moduleName !== 'uixul') 
			{
				return $this->LoadWidgetConfig('uixul', $widgetId);
			}	
		} 
		elseif (is_readable($widgetXmlFilePath))
		{
			$domDoc = new DOMDocument('1.0', 'UTF-8');
			$domDoc->preserveWhiteSpace = false;
			if (!$domDoc->load($widgetXmlFilePath))
			{
				throw new Exception("XML not well-formed in \"$widgetXmlFilePath\".");
			}	
			return $domDoc;		
		}
		return null;
	}
	
	/**
	 * @param string $binding
	 * @return string
	 */
	public function buildBinding($binding)
	{
		$templateObject = uixul_lib_BindingObject::getTemplateObject($binding);
		$xml = $templateObject->execute();
		if (substr($binding, 0, strpos($binding, '.')) == 'modules')
		{
			list (, $modName, $bindingName) = explode('.', $binding);
			$className = "" . $modName . "_lib_" . $bindingName . "TagReplacer";
		}
		else
		{
			$className = "uixul_lib_" . substr($binding, strrpos($binding, '.') + 1) . "TagReplacer";
		}
		
		if (f_util_ClassUtils::classExists($className))
		{
			$tagReplacer = new $className();
		}
		else
		{
			$tagReplacer = new f_util_TagReplacer();
		}
		
		$tagReplacer->setReplacement('ControllerUrl', Framework::getUIBaseUrl() . "/xul_controller.php");
		$tagReplacer->setReplacement('HttpHost', Framework::getUIBaseUrl());
		$tagReplacer->setReplacement('IconsBase', MediaHelper::getIconBaseUrl());
		
		$xml = $tagReplacer->run($xml, true);
		
		$matches = array();
		preg_match_all('/extends="([^\"]*)"/', $xml, $matches, PREG_SET_ORDER);
		if (! empty($matches))
		{
			foreach ($matches as $match)
			{
				$extendsAttribute = $match[0];
				$extendsAttributeValue = $match[1];
				if (! preg_match('/^\w+:/i', $extendsAttributeValue))
				{
					list ($bindingFile, $bindingId) = explode('#', $extendsAttributeValue, 2);
					$getBindingUrl = uixul_lib_BindingObject::getUrl($bindingFile, true);
					$xml = str_replace($extendsAttribute, 
							'extends="' . $getBindingUrl . '#' . $bindingId . '"', $xml);
				}
			}
		}
		
		$matches = array();
		preg_match_all('/<stylesheet\ssrc="([^\"]*)"\s*\/>/', $xml, $matches, PREG_SET_ORDER);
		if (! empty($matches))
		{
			foreach ($matches as $match)
			{
				$stylesheetDeclaration = $match[0];
				$stylesheetSrc = $match[1];
				$stylesheetLink = LinkHelper::getUIChromeActionLink("uixul", "GetUICSS")->setQueryParameter(
						'uilang', RequestContext::getInstance()->getUILang())->setQueryParameter(
						'stylename', $stylesheetSrc)->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
				$xml = str_replace($stylesheetDeclaration, '<stylesheet src="' . $stylesheetLink->getUrl() . '" />', $xml);
			}
		}
		return $xml;
	}
}