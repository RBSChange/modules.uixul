<?php
/**
 * uixul_BindingService
 * @package modules.uixul.lib.services
 */
class uixul_BindingService extends BaseService
{

	/**
	 * Singleton
	 * @var uixul_BindingService
	 */
	private static $instance = null;

	/**
	 * @return uixul_BindingService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
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
			    ->setQueryParameter('perspective' , 'default')
			    ->setFragment('wModule-'.$moduleName)
			    ->getUrl();
		$result[] = 'wmodule[name="'.$moduleName.'"] {-moz-binding: url('.$url.');}';	
		return implode("\n", $result);
	}
	
	/**
	 * @param string $moduleName
	 * @return string
	 */
	public function getForms($moduleName)
	{
		$result = array();
		$forms = ModuleService::getInstance()->getAvailableForms($moduleName);

		// for each document found, create the CSS rule...
		foreach ($forms as $form)
		{
			// ... only if the template exists!
			$mod = $form == 'folder' ? K::GENERIC_MODULE_NAME : $moduleName;
			$url = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')
				->setQueryParameter('uilang', RequestContext::getInstance()->getUILang())
			    ->setQueryParameter('wemod', $moduleName)
			    ->setQueryParameter('binding', 'modules.'.$mod.'.form.'.$form)
			    ->setFragment('wForm-'.$mod.'-'.$form)
			    ->getUrl();

			$result[] = 'wform[module="'.$mod.'"][document-type="'.$form.'"] {-moz-binding: url('.$url.');}';
			if ($mod != $moduleName)
			{
				$result[] = 'wform[module="'.$moduleName.'"][document-type="'.$form.'"] {-moz-binding: url('.$url.');}';
			}
		}
		
		return implode("\n", $result);
	}
	
	/**
	 * @param string $moduleName
	 * @return string
	 */
	public function getWidgets($moduleName)
	{
		$result = array();		
		$widgetTagsById = uixul_lib_UiService::getWidgetsInPerspective($moduleName);
		$widgets = array_keys(uixul_lib_UiService::getDefinedWidgets($moduleName));
		foreach ($widgets as $widgetId)
		{
		    if (isset($widgetTagsById[$widgetId]))
		    {
    			$tagName = $widgetTagsById[$widgetId];
    			if ($tagName[0] === 'w') {$tagName = 'w' . ucfirst(substr($tagName, 1));}

    			$url = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')
    			->setQueryParameter('uilang', RequestContext::getInstance()->getUILang())
			    ->setQueryParameter('wemod', $moduleName)
			    ->setQueryParameter('binding', 'widgets.'.$tagName)
			    ->setQueryParameter('widgetref', $widgetId)
			    ->setFragment($tagName)
			    ->getUrl();	    
    			$result[] = '#modules_'.$moduleName.'_widget_'.$widgetId.' {-moz-binding: url('.$url.');}';
		    }
		}
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
	 * @param String $moduleName
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
}