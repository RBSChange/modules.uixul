<?php
class uixul_lib_UiService
{
	protected static $actionStack;

	const SETTING_ROOT_FOLDER_ID = 'root_folder_id';
	const SETTING_PREFERENCES_DOCUMENT_ID = 'preferences_document_id';
	const SETTING_PREFERENCES_DOCUMENT_TYPE = 'preferences';

	const UI_MODULE_NAME = 'uixul';

	/**
	 * Retrieves the actions available in a module and returns them in the
	 * $actions parameter (passed by reference).
	 *
	 * @param string moduleName The module name.
	 * @param array $actions The found actions will be placed in this array.
	 */
	public static function getModuleActions($moduleName, &$actions)
	{
		$actionsFile = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)
						->setDirectory('config')->getPath('actions.xml');
		
		$baseAction = array();
		if ($moduleName !== self::UI_MODULE_NAME)
		{
			self::getModuleActions(self::UI_MODULE_NAME, $baseAction);
		}
		
		if ($actionsFile != null)
		{
			$document = new DOMDocument('1.0', 'UTF-8');
			$document->load($actionsFile);
						
			$xpath = new DOMXPath($document);
			$entries = $xpath->query('//action[@name]');
			foreach ($entries as $entry) 
			{
				$name = $entry->getAttribute('name');
				if (!isset($baseAction[$name]))
				{
					$action = new uixul_lib_Action();
					$action->name = $name;	
				}
				else
				{
					$action = $baseAction[$name];
					$action->parameters = array();
				}
				
				$actions[$action->name] = $action;
				if ($entry->hasAttribute('label'))
				{
					$action->label = f_Locale::translateUI($entry->getAttribute('label'));
				}
				if ($entry->hasAttribute('icon'))
				{
					$action->icon = $entry->getAttribute('icon');
				}
				if ($entry->hasAttribute('global'))
				{
					$action->global = ($entry->getAttribute('global') == 'true');
				}
				if ($entry->hasAttribute('hasSeparator'))
				{
					$action->hasSeparator = ($entry->getAttribute('hasSeparator') == 'true');
				}				

				foreach ($xpath->query('parameter[@name]', $entry) as $parameter)
				{
					$action->parameters[] = $parameter->getAttribute('name');
				}

				foreach ($xpath->query('body', $entry) as $body)
				{
					$action->body = $body->textContent;
				}
				
				foreach ($xpath->query('checkDisplay', $entry) as $checkDisplay)
				{
					$action->checkDisplay = $checkDisplay->textContent;
				}
				
				
				if ($entry->getAttribute('selectionType') == 'single')
				{
					if (f_util_StringUtils::isEmpty($action->checkDisplay))
					{
						$action->checkDisplay = 'return countDocument == 1;';
					}
					else
					{
						$action->checkDisplay = "if (countDocument != 1) {return false;}\n" . $action->checkDisplay;
					}
				}	
			}	
		}	
	}


	/**
	 * @param string $moduleName
	 * @return array<string>
	 */
	public static function getPerspectives($moduleName)
	{
		return array('default');
	}

	/**
	 * @param string $moduleName
	 * @return array<string>
	 */
	public static function getDefinedWidgets($moduleName)
	{
		$widgets = array();
		$widgetsConfigDirArray = ModuleService::getInstance()->getModulePath($moduleName, '/config/widgets/', 'all');
        if ($widgetsConfigDirArray === null)
        {
             return $widgets;
        }
		foreach ($widgetsConfigDirArray as $widgetsConfigDir)
		{
			$dirObject = dir($widgetsConfigDir);
			while ( $entry = $dirObject->read() )
			{
				if (strpos($entry, '.xml') !== false)
				{
					$widgetId = substr($entry, 0, strrpos($entry, '.'));
					if (!array_key_exists($widgetId, $widgets))
					{
						$widgets[$widgetId] = $widgetsConfigDir . $entry;
					}
				}
			}
			$dirObject->close();
		}
		return $widgets;
	}


	public static function getDefinedWidgetsInfo($moduleName)
	{
		$widgetInfos = array();
		$widgets = self::getDefinedWidgets($moduleName);
		
		foreach ($widgets as $name => $widgetFile)
		{
			$widgetInfo = array();
			$widgetInfo['file'] = $widgetFile;
			$widgetInfo['name'] = $name;
			$xml = simplexml_load_file($widgetFile);
			if ($xml instanceof SimpleXMLElement)
			{
				if (strval($xml['allow-toggle']) == 'true' && strval($xml['label']) != '')
				{
					$widgetInfo['allow-toggle'] = true;
					$widgetInfo['label'] = strval($xml['label']);
				}
			}
			$widgetInfos[$name] = $widgetInfo;
		}
		return $widgetInfos;
	}

	public static function getWidgetsInPerspective($moduleName)
	{
		$widgets = array();
		$perspectiveFile = FileResolver::getInstance()
							->setPackageName('modules_' . $moduleName)
							->setDirectory('templates/perspectives')
							->getPath('default.all.all.xul');

		if (!is_null($perspectiveFile))
		{
			$perspectiveContents = '<?xml version="1.0" encoding="UTF-8"?><perspective>' . str_replace(array("tal:", "i18n:", "change:"), "", file_get_contents($perspectiveFile)) . '</perspective>';
			$perspectiveContents = preg_replace('/&[^;]+;/', '', $perspectiveContents);
			
			$xmlData = new DOMDocument();
			$xmlData->loadXML($perspectiveContents);
			$xpathObject = new DOMXPath($xmlData);
			$widgetNodes = $xpathObject->query("//*[@id]");

			foreach ($widgetNodes as $widgetNode)
			{
				$widgets[$widgetNode->getAttribute("id")] = $widgetNode->tagName;
			}
		}
		return $widgets;
	}

	/**
	 * @param string $moduleName
	 * @param string $contents
	 */
	public static function translateAnonidToId($moduleName, &$contents)
	{
		$prefix = '<?xml version="1.0" encoding="UTF-8"?><root>';
		$suffix = '</root>';		
		$xmlData = new DOMDocument();
		$xmlData->loadXML($prefix . $contents . $suffix);
		$xpathObject = new DOMXPath($xmlData);
		$widgetNodes = $xpathObject->query("//*[@id]");
		foreach ($widgetNodes as $widgetNode)
		{
			if ($widgetNode->hasAttribute("anonid")) {continue;}

			$widgetId = $widgetNode->getAttribute("id");
			$widgetNode->setAttribute("id", "modules_".$moduleName."_widget_".$widgetId);
			$widgetNode->setAttribute("anonid", $widgetId);			
		}
			
		$widgetNodes = $xpathObject->query("//*[@attachment]");
		foreach ($widgetNodes as $widgetNode)
		{
			$attachmentIds = array();
			$attachmentArray = explode(" ", $widgetNode->getAttribute("attachment"));
			foreach ($attachmentArray as $attachment)
			{
				if (trim($attachment))
				{
					$attachmentIds[] = "modules_".$moduleName."_widget_".$attachment;
				}
			}
			if (count($attachmentIds) > 0)
			{
				$widgetNode->setAttribute("attachment", join(" ", $attachmentIds));
			}		
		}
		$contents = substr($xmlData->SaveXML(), strlen($prefix)+1, -strlen($suffix)-1);
	}

	public static function buildModuleBinding($moduleName)
	{
		$config = uixul_ModuleBindingService::getInstance()->loadConfig($moduleName);
		if ($config)
		{
			return uixul_ModuleBindingService::getInstance()->buildModuleBinding($moduleName, $config);
		}
		
		// Handle wModule extends.
		$extends = uixul_lib_BindingObject::getUrl('core.wBaseModule', true) . '#wBaseModule';
		
		// ---------------------------------------------------------------------
		// Begin i18n work
		// ---------------------------------------------------------------------

		$rq = RequestContext::getInstance();
        $rq->beginI18nWork($rq->getUILang());
     
		// Build binding's methods (XML) from the actions definition (actions.xml).
		$actions = array();
		self::getModuleActions($moduleName, $actions);
    	$actionsToMethodsTransformer = new uixul_ActionsToMethodsTransformer($moduleName);
    	$methodArray = array();
    	$initCodeArray = array();
    	// $methodArray and $initCodeArray are passed by reference
    	$actionsToMethodsTransformer->transform($actions, $methodArray, $initCodeArray);

		// ---------------------------------------------------------------------
		// Build module contents
		// ---------------------------------------------------------------------

    	$templateLoader = TemplateLoader::getInstance();
		$templatePath = 'templates/perspectives';
		
		if ('uixul' !== $moduleName)
		{
			$templateLoader->setMimeContentType('xul')->setDirectory($templatePath);
			$templateObject = $templateLoader
					->setPackageName('modules_' . $moduleName)
					->load('default');

			// -----------------------------------------------------------------
			// Build module header:
			// Global actions, preferences, close button.
			// -----------------------------------------------------------------
			
			$tagReplacer = new f_util_TagReplacer();
								
			$moduleContents = $tagReplacer->run($templateObject->execute(), true);
			uixul_lib_UiService::translateAnonidToId($moduleName, $moduleContents);
			
		} // end of if ('uixul' !== $moduleName)
		else
		{
			// Module 'uixul' has no content.
			$moduleContents = '';
		}

		$templateObject = $templateLoader->reset()->setPackageName('modules_uixul')->setMimeContentType('xml')
			->load('Uixul-Module-Binding');

		$templateObject->setAttribute('methods', join(PHP_EOL, $methodArray));
		$templateObject->setAttribute('init', "<![CDATA[\n" . join(";\n", $initCodeArray) . "]]>\n");
		$templateObject->setAttribute('bindingId', 'wModule-'.$moduleName);
		$templateObject->setAttribute('extends', $extends);
		$templateObject->setAttribute('generationDate', sprintf("<!-- generation time: %s -->", time()));
		$templateObject->setAttribute('moduleContents', $moduleContents);

		// Document injection.
		$impl = '<method name="setupDocumentMapping"><body><![CDATA[';
		try
		{
			$injectionConfig = Framework::getConfiguration('injection');
			if (isset($injectionConfig['document']))
			{
				$documentInjectionArray = $injectionConfig['document'];
				$documentModuleMapping = array();
				$documentNameMapping = array();

				foreach ($documentInjectionArray as $source => $target)
				{
					list($sourceModule, $sourceDocument) = explode('/', $source);
					if ($sourceModule == $moduleName)
					{
						list($targetModule, $targetDocument) = explode('/', $target);
						$documentModuleMapping[] = $sourceDocument.": '".$targetModule."'";
						$documentNameMapping[] = $sourceDocument.": '".$targetDocument."'";
					}
				}

				$impl .= "this.documentNameMapping = { ".implode(', ', $documentNameMapping) . "};\n";
				$impl .= "this.documentModuleMapping = { ".implode(', ', $documentModuleMapping) . "};\n";
			}

		}
		catch (ConfigurationException $e)
		{
			// This might happen if no injection is configured.
			Framework::exception($e);
		}
		$impl .= ']]></body></method>';
		$templateObject->setAttribute('implementation', $impl);
		
		$xml = $templateObject->execute();
		$xml = str_replace(array('{HttpHost}', '{IconsBase}'), array(Framework::getUIBaseUrl(), MediaHelper::getIconBaseUrl()), $xml);
		$rq->endI18nWork();
		return $xml;
	}
}


/**
 * A class to transform an array of ActionObject into an XML representation
 * of methods used inside a XUL binding.
 *
 * @author intbonjf
 * @package modules.uixul
 */
class uixul_ActionsToMethodsTransformer
{
	/**
	 * @var string
	 */
	private $moduleName;

	/**
	 * @param string $moduleName
	 */
	public function __construct($moduleName)
	{
		$this->moduleName = $moduleName;
	}

	/**
	 * @param array<ActionObject> $actionArray
	 * @param &array<string> $methodArray
	 * @param &array<string> $initCodeArray
	 */
	public function transform($actionArray, &$methodArray, &$initCodeArray)
	{	
		$tagReplacer = new f_util_TagReplacer();
		foreach ($actionArray as $actionName => $actionObject)
		{
			if ($actionObject->body)
			{
				$body = str_replace('%label%', f_Locale::translate($actionObject->label), $actionObject->body);
				$body = $tagReplacer->run($body, true);

				$parameters = array();
				foreach ($actionObject->parameters as $parameterName)
				{
					$parameters[] = sprintf('<parameter name="%s" />', $parameterName);
				}

				$preMethod  = 'pre'.ucfirst($actionObject->name);
				$postMethod = 'post'.ucfirst($actionObject->name);
				$body = "if ('$preMethod' in this) try{this.$preMethod(arguments);}catch(e){wCore.debug(e);return;}\n" . $body . "\nif ('$postMethod' in this) this.$postMethod(arguments);\n";
				$methodArray[] = sprintf(
					"<method name=\"%s\">\n%s\n<body><![CDATA[\n%s\n]]>\n</body>\n</method>\n\n",
					$actionObject->name, join(PHP_EOL, $parameters), $body
					);
			}

			$method = sprintf(
				"<method name=\"checkDisplay_%s\">\n<parameter name=\"document\"/>\n<parameter name=\"countDocument\"/>\n<body><![CDATA[\n%s\n]]>\n</body>\n</method>\n\n",
				$actionObject->name, $actionObject->checkDisplay
				);
			$methodArray[] = $method;


			$initCodeArray[] = sprintf(
					'this._actionInformation["%s"] = { label: "%s", image: "%s", localized: %s }',
					$actionName,
					f_Locale::translate($actionObject->label),
					MediaHelper::getIcon($actionObject->icon, MediaHelper::SMALL, null, MediaHelper::LAYOUT_SHADOW),
					$actionObject->localized ? 'true' : 'false'
					);
		}
		
		$widgetsInfos = uixul_lib_UiService::getDefinedWidgetsInfo($this->moduleName);
		if (count($widgetsInfos) > 0)
		{
			$initCodeArray[] = sprintf('this._lists = ["%s"];', join('", "', array_keys($widgetsInfos)));
		}
		else
		{
			$initCodeArray[] = 'this._lists = [];';
		}
	}
}