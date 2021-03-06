<?php
class uixul_PropertyGridBindingService extends BaseService
{
	/**
	 * Singleton
	 * @var uixul_PropertyGridBindingService
	 */
	private static $instance = null;
	
	/**
	 * @return uixul_PropertyGridBindingService
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
	 * @param string $blockName
	 * @return string
	 */
	public function getBinding($moduleName, $blockName)
	{
		if (headers_sent() === false)
		{
			header('Content-type: text/xml');
		}
		
		$configDocument = $this->getConfig($moduleName, $blockName);
		
		$binding = $this->buildPropertyGridBinding($configDocument);
		$xpath = $this->getBindingXPath($binding);
		$bindingNodes = $xpath->query('//xbl:binding[@extends]');
		foreach ($bindingNodes as $bindingNode)
		{
			$extend = uixul_lib_BindingObject::getUrl($bindingNode->getAttribute("extends"));
			$bindingNode->setAttribute("extends", $extend);
		}
		
		$binding->formatOutput = true;
		$tr = new f_util_TagReplacer();
		$tr->setReplacement('HttpHost', Framework::getUIBaseUrl());
		$tr->setReplacement('IconsBase', MediaHelper::getIconBaseUrl());
		return $tr->run($binding->saveXML(), true);
	}
	private function getConfig($moduleName, $blockName)
	{
		$bi = block_BlockService::getInstance()->getBlockInfo($blockName);
		
		$configDocument = f_util_DOMUtils::newDocument();
		$configPaths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('config')->getPaths('blocks.xml');
		
		if (f_util_ArrayUtils::isNotEmpty($configPaths))
		{
			foreach (array_reverse($configPaths) as $configPath)
			{
				$document = f_util_DOMUtils::fromPath($configPath);
				$blockElem = $document->findUnique('//block[@type="' . $blockName . '"]');
				if ($blockElem !== null)
				{
					$this->appendConfig($configDocument, $blockElem, $bi);
				}
			}
		}
		
		if ($bi->getInjectedBy())
		{
			$injectedType = $bi->getInjectedBy();
			list (, $moduleName, ) = explode('_', $injectedType);
			$configPaths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('config')->getPaths('blocks.xml');
			if (f_util_ArrayUtils::isNotEmpty($configPaths))
			{
				foreach (array_reverse($configPaths) as $configPath)
				{
					$document = f_util_DOMUtils::fromPath($configPath);
					$blockElem = $document->findUnique('//block[@type="' . $injectedType . '"]');
					if ($blockElem !== null)
					{
						$this->appendConfig($configDocument, $blockElem, $bi);
					}
				}
			}
		}
		
		$this->appendMetaParameter($configDocument, $bi);
		
		$this->appendUseCache($configDocument, $bi);
		
		return $configDocument;
	}
	
	/**
	 * 
	 * @param f_util_DOMDocument $configDocument
	 * @param DOMElement $blockElem
	 * @param block_BlockInfo $blockInfo
	 */
	private function appendConfig($configDocument, $blockElem, $blockInfo)
	{
		$document = $blockElem->ownerDocument;
		if ($configDocument->documentElement !== null)
		{
			// Import parameters
			$parameters = $document->find("parameters/parameter", $blockElem);
			if ($parameters->length > 0)
			{
				$parametersElem = $configDocument->createIfNotExists("parameters", $configDocument->documentElement);
				foreach ($parameters as $parameter)
				{
					$newParamElem = $configDocument->importNode($parameter, true);
					$oldParamElem = $configDocument->findUnique("parameter[@name = '" . $newParamElem->getAttribute("name") . "']", $parametersElem);
					if ($oldParamElem !== null)
					{
						$parametersElem->replaceChild($newParamElem, $oldParamElem);
					}
					else
					{
						$parametersElem->appendChild($newParamElem);
					}
				}
			}
			
			// Import XUL
			$javascript = $document->findUnique("xul/javascript", $blockElem);
			if ($javascript !== null)
			{
				$configJsElem = $configDocument->createIfNotExists("xul/javascript", $configDocument->documentElement);
				foreach ($javascript->childNodes as $jsChildNode)
				{
					if ($jsChildNode->nodeType == XML_ELEMENT_NODE)
					{
						$jsElem = $configDocument->importNode($jsChildNode, true);
						$jsXPath = $jsElem->tagName;
						if ($jsElem->hasAttribute("name"))
						{
							$jsXPath .= "[@name = '" . $jsElem->getAttribute("name") . "']";
						}
						$jsElemFromConfig = $configDocument->findUnique($jsXPath, $configJsElem);
						if ($jsElemFromConfig === null)
						{
							$configJsElem->appendChild($jsElem);
						}
						else
						{
							$configJsElem->replaceChild($jsElem, $jsElemFromConfig);
						}
					}
				}
			}
		}
		else
		{
			$configDocument->appendChild($configDocument->importNode($blockElem, true));
		}
		
		if (!$configDocument->documentElement->hasAttribute('icon'))
		{
			$configDocument->documentElement->setAttribute('icon', 'document');
		}
		
		if (!$configDocument->documentElement->hasAttribute('label'))
		{
			$configDocument->documentElement->setAttribute('label', f_Locale::translateUI($blockInfo->getLabel()));
		}
	}
	
	/**
	 * @param f_util_DOMDocument $configDocument
	 * @param block_BlockInfo $blockInfo
	 */
	private function appendMetaParameter($configDocument, $blockInfo)
	{
		if ($blockInfo && $blockInfo->hasMeta() && !$configDocument->exists("parameters/parameter[@name = 'enablemetas']", $configDocument->documentElement))
		{
			$parametersElem = $configDocument->findUnique("parameters", $configDocument->documentElement);
			if ($parametersElem === null)
			{
				$parametersElem = $configDocument->createElement("parameters");
				$configDocument->documentElement->appendChild($parametersElem);
			}
			$parameterElem = $configDocument->createElement("parameter");
			// WARN: synchronize with block_BlockService::completeBlockInfoWithMetas()
			$parameterElem->setAttribute("name", "enablemetas");
			$parameterElem->setAttribute("type", "Boolean");
			$parameterElem->setAttribute("default-value", "true");
			$parameterElem->setAttribute("labeli18n", 'modules.website.bo.blocks.Enablemetas');
			$parameterElem->setAttribute("shorthelpi18n", 'modules.website.bo.blocks.Enablemetas-help');
			$parametersElem->appendChild($parameterElem);
		}
	}
	
	/**
	 * @param f_util_DOMDocument $configDocument
	 * @param block_BlockInfo $blockInfo
	 */
	private function appendUseCache($configDocument, $blockInfo)
	{
		if ($blockInfo && $configDocument->documentElement->hasAttribute("cache") && $configDocument->documentElement->getAttribute("cache") != "false")
		{
			$parametersElem = $configDocument->findUnique("parameters", $configDocument->documentElement);
			if ($parametersElem === null)
			{
				$parametersElem = $configDocument->createElement("parameters");
				$configDocument->documentElement->appendChild($parametersElem);
			}
			$parameterElem = $configDocument->createElement("parameter");
			// WARN: synchronize with block_BlockService::completeBlockInfoWithMetas()
			$parameterElem->setAttribute("name", "cusecache");
			$parameterElem->setAttribute("type", "Boolean");
			$parameterElem->setAttribute("default-value", "true");
			$parameterElem->setAttribute("labeli18n", 'modules.website.bo.blocks.Usecache');
			$parameterElem->setAttribute("shorthelpi18n", 'modules.website.bo.blocks.Usecache');
			$parametersElem->appendChild($parameterElem);
		}
	}
	
	/**
	 * @return DOMDocument
	 */
	private function getNewBindingsDOMDocument()
	{
		$domDocument = $this->getNewDOMDocument();
		$domDocument->loadXML('<?xml version="1.0" encoding="UTF-8"?><bindings xmlns="http://www.mozilla.org/xbl" xmlns:xbl="http://www.mozilla.org/xbl" xmlns:html="http://www.w3.org/1999/xhtml" xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul" />');
		return $domDocument;
	}
	
	/**
	 * @param DOMDocument $document
	 * @return DOMXPath
	 */
	private function getBindingXPath($document)
	{
		$xpath = new DOMXPath($document);
		$xpath->registerNamespace('xbl', 'http://www.mozilla.org/xbl');
		return $xpath;
	}
	
	/**
	 * @param DOMDocument $configDocument
	 * @return DOMDocument
	 */
	private function buildPropertyGridBinding($configDocument)
	{
		$xslPath = FileResolver::getInstance()->setPackageName('modules_uixul')->setDirectory(f_util_FileUtils::buildPath('forms', 'grid'))->getPath('properties.xsl');
		$xsl = new DOMDocument('1.0', 'UTF-8');
		$xsl->load($xslPath);
		$xslt = new XSLTProcessor();
		$xslt->registerPHPFunctions();
		$xslt->importStylesheet($xsl);
		
		self::$XSLCurrentFields = array();
		$type = $configDocument->documentElement->getAttribute("type");
		$parts = explode('_', $type);
		self::$XSLBaseId = 'pg_' . $parts[1] . '_' . $parts[2] . '_';
		self::$XSLModuleName = $parts[1];
		self::$XSLBlockName = $parts[2];
		return $xslt->transformToDoc($configDocument);
	}
	
	/**
	 * @var string
	 */
	private static $XSLBaseId;
	
	/**
	 * @var string
	 */
	private static $XSLModuleName;
	
	/**
	 * @var string
	 */
	private static $XSLBlockName;
	
	/**
	 * @var array
	 */
	private static $XSLCurrentFields;
	
	/**
	 * @param array $elementArray
	 * @return string
	 */
	public static function XSLSetDefaultFieldInfo($elementArray)
	{
		$element = $elementArray[0];
		$name = $element->getAttribute("name");
		self::$XSLCurrentFields[$name] = true;
		
		self::updatePropertyField($element);

		$element->setAttribute("cntanonid", $name . '_cnt');
		return $element;
	}
	
	/**
	 * @return string
	 */
	public static function XSLFieldsName()
	{
		return JsonService::getInstance()->encode(array_keys(self::$XSLCurrentFields));
	}
	
	/**
	 * @param DOMElement $element
	 */
	private static function updatePropertyField($element)
	{
		$propertyName = $element->getAttribute("name");
		$type = $element->getAttribute("type");
		if ($element->hasAttribute("list-id"))
		{
			$listid = $element->getAttribute("list-id");
		}
		else if ($element->hasAttribute("from-list"))
		{
			$listid = $element->getAttribute("from-list");
		}
		else
		{
			$listid = false;
		}
		
		$element->setAttribute('id', self::$XSLBaseId . $propertyName);
		$element->setAttribute('anonid', 'prop_' . $propertyName);
		$maxOccurs = $element->hasAttribute('max-occurs') ? intval($element->getAttribute('max-occurs')) : 1;
		if ($maxOccurs != 1)
		{
			$constraintArray = array();
			$minOccurs = $element->hasAttribute('min-occurs') ? intval($element->getAttribute('min-occurs')) : 1;
			if ($minOccurs > 0)
			{
				$constraintArray['minSizeArray'] = $minOccurs;
			}
			// case -1 (for no limit) : no constraint.
			if ($maxOccurs > 1)
			{
				$constraintArray['maxSizeArray'] = $maxOccurs;
			}
			self::addContraints($element, $constraintArray);
		}
		
		$isArray = ($maxOccurs != 1);
		if ($listid)
		{
			$element->setAttribute('listid', $listid);
			$type = $isArray ? "multiplelist" : "dropdownlist";
		}
		else if (strpos($type, '/') !== false)
		{
			$doctype = str_replace('/', '_', $type);
			$parts = explode("_", $doctype);
			if (!$element->hasAttribute('moduleselector'))
			{
				$element->setAttribute('moduleselector', $parts[1]);
			}
			if (!$element->hasAttribute('allow'))
			{
				$element->setAttribute('allow', $doctype);
			}
			$type = $isArray ? "documentarray" : "document";
		}
		else
		{
			switch ($type)
			{
				case 'Boolean' :
					$type = 'boolean';
					break;
				case 'Integer' :
					$type = 'integer';
					break;
				case 'Integer' :
					$type = 'integer';
					break;
				case 'Double' :
					$type = 'double';
					break;
				case 'DateTime' :
					$type = 'datetime';
					break;
				case 'Lob' :
				case 'LongString' :
					$type = 'longtext';
					break;
				case 'XHTMLFragment' :
					$type = 'richtext';
					break;
				default :
					$type = 'text';
					break;
			}
		}
		
		if ($element->hasAttribute('fieldtype'))
		{
			$type = $element->getAttribute('fieldtype');
		}
		$element->setAttribute('type', $type);
		
		if (intval($element->getAttribute('min-occurs')) > 0)
		{
			$element->setAttribute('required', 'true');
		}
		
		if ($element->hasAttribute('default-value'))
		{
			$element->setAttribute('initialvalue', $element->getAttribute('default-value'));
		}
		
		if (!$element->hasAttribute("labeli18n"))
		{
			$labeli18n = 'modules.' . self::$XSLModuleName . '.bo.blocks.' . self::$XSLBlockName . '.' . ucfirst($propertyName);
			$element->setAttribute('labeli18n', $labeli18n);
		}
		
		if ($element->getAttribute('hidehelp') == 'true')
		{
			$element->removeAttribute('shorthelpi18n');
		}
		else if (!$element->hasAttribute('shorthelpi18n'))
		{
			$shorthelpi18n = 'modules.' . self::$XSLModuleName . '.bo.blocks.' . self::$XSLBlockName . '.' . ucfirst($propertyName) . "-help";
			$element->setAttribute('shorthelpi18n', $shorthelpi18n);
		}
		$element->setAttribute('hidehelp', 'true');
		
		$constraints = $element->getElementsByTagName('constraints');
		if ($constraints->length > 0)
		{
			$val = $constraints->item(0)->textContent;
			$constraintsParser = new validation_ContraintsParser();
			$constraintArray = $constraintsParser->getConstraintArrayFromDefinition($val);
			self::addContraints($element, $constraintArray);
		}
	}
	
	/**
	 * @param DOMElement $element
	 * @param array $constraintArray
	 */
	protected static function addContraints($element, $constraintArray)
	{
		foreach ($constraintArray as $name => $value)
		{
			if ($name === 'blank')
			{
				continue;
			}
			$cn = $element->appendChild($element->ownerDocument->createElement('constraint'));
			$cn->setAttribute('name', $name);
			$cn->setAttribute('parameter', $value);
		}
	}
}