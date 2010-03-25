<?php
class uixul_FormBindingService extends uixul_BaseBindingService
{
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @param String $moduleName
	 * @param String $documentName
	 * @return DomDocument
	 */
	private function getLayoutDomDocForModel($documentModel, $moduleName = null, $documentName = null)
	{
		if(is_null($moduleName) && !is_null($documentModel))
		{
			$moduleName = $documentModel->getModuleName();
		}
		if(is_null($documentName) && !is_null($documentModel))
		{
			$documentName = $documentModel->getDocumentName();
		}

		// Load layout definition
		$formTemplateFile = Resolver::getInstance('file')
			->setPackageName('modules_' . $moduleName)
			->setDirectory('forms')
			->getPath($documentName . '_layout.all.all.xul');
		if ( is_null($formTemplateFile) )
		{
			return null;
		}
		$layoutDomDoc = new DOMDocument();
		$layoutDomDoc->preserveWhiteSpace = false;
		if ( ! $layoutDomDoc->load($formTemplateFile) )
		{
			throw new Exception("XML not well-formed in \"$formTemplateFile\".");
		}
		return $layoutDomDoc;
	}

	/**
	 * @param String $blockName
	 * @return DomDocument
	 */
	private function getLayoutDomDocForBlockName($blockName)
	{
		$layoutDomDoc = new DOMDocument();
		$layoutDomDoc->preserveWhiteSpace = false;

		$blockInfo = block_BlockService::getInstance()->getBlockInfo($blockName);		
		$propertyArray = $blockInfo->getPropertyGridParameters();
		
		// intbonjf 2008-05-29: generate the property grid only when there are block parameters.
		if (count($propertyArray) > 0)
		{
			$contentTemplateFile = "Uixul-BlockPropertyGrid-Header";

			$templateObject = TemplateLoader::getInstance()->setPackageName('modules_uixul')->setMimeContentType(K::XUL)->load($contentTemplateFile);
			if ( ! $layoutDomDoc->loadXML($templateObject->execute()) )
			{
				throw new Exception("XML not well-formed in template \"$contentTemplateFile\".");
			}

			$xpath = new DOMXPath($layoutDomDoc);
			$xpath->registerNamespace('xul', self::NS_XUL);

			// Set label element with block's label.
			$nodeList = $xpath->query('//xul:label[@anonid="propertyGridLabel"]');
			if ($nodeList->length != 1)
			{
				throw new Exception("Malformed block property grid layout: must contain a unique \"label\" element with anonid=\"propertyGridLabel\" in \"$contentTemplateFile\".");
			}
			$labelElm = $nodeList->item(0);
			$labelElm->setAttribute('value', f_Locale::translate($blockInfo->getLabel()));

			// Set image element with block's icon.
			$nodeList = $xpath->query('//xul:image[@anonid="propertyGridIcon"]');
			if ($nodeList->length == 1)
			{
				$imageElm = $nodeList->item(0);
				$size = $imageElm->hasAttribute('size') ? $imageElm->getAttribute('size') : 'small';
				$imageElm->removeAttribute('size');
				$imageElm->setAttribute('src', MediaHelper::getIcon($blockInfo->getIcon(), $size));
			}

			$nodeList = $xpath->query('//xul:*[@anonid="mainContainer"]');

			if ($nodeList->length != 1)
			{
				throw new Exception("Malformed block property grid layout: must contain a unique \"vbox\" element with anonid=\"mainContainer\" in \"$contentTemplateFile\".");
			}
			$contentNode = $nodeList->item(0);

			foreach ($propertyArray as $property)
			{
				
				$changeLabel = $layoutDomDoc->createElementNS(self::NS_CHANGE, 'label');
				$changeLabel->setAttribute('field', $property->getName());
				$changeField = $layoutDomDoc->createElementNS(self::NS_CHANGE, 'field');
				$changeField->setAttribute('name', $property->getName());
				$boxElm = $layoutDomDoc->createElement('box');
				$boxElm->setAttribute('class', 'header');
				$boxElm->appendChild($changeLabel);
				$contentNode->appendChild($boxElm);
				$boxElm = $layoutDomDoc->createElement('box');
				$boxElm->setAttribute('class', 'control');
				$boxElm->appendChild($changeField);
				$contentNode->appendChild($boxElm);
			}
		}

		return $layoutDomDoc;
	}

	/**
	 * @param String $blockName
	 * @return DomDocument
	 */
	private function getTemplateDomDocumentForBlockName($blockName)
	{
		$domDoc = null;
		$layoutDomDoc = $this->getLayoutDomDocForBlockName($blockName);

		// Create final DomDocument object
		$domDoc = new DOMDocument();
		$domDoc->preserveWhiteSpace = false;
		$domDoc->loadXML(
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<binding xmlns="'.self::NS_XBL.'">'
			. '<xbl:content xmlns="'.self::NS_XUL.'" xmlns:xbl="'.self::NS_XBL.'" xmlns:i18n="'.self::NS_I18N.'" xmlns:change="'.self::NS_CHANGE.'">'
			. '</xbl:content>'
			. '</binding>'
			);
		$rootNode = $domDoc->documentElement;
		$contentNode = $rootNode->firstChild;

		// Import layout nodes into final DomDocument object
		$layoutXPath = new DOMXPath($layoutDomDoc);
		$layoutXPath->registerNamespace('xul', self::NS_XUL);
		$nodeList = $layoutXPath->query('/xul:layout/*');
		for ($i = 0 ; $i < $nodeList->length ; $i++)
		{
			$contentNode->appendChild($domDoc->importNode($nodeList->item($i), true));
		}

		$propertyArray = block_BlockService::getInstance()->getBlockInfo($blockName)->getPropertyGridParameters();

		$this->replaceFieldsAndLabels($domDoc, $propertyArray, null);

		return $domDoc;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @param String $moduleName
	 * @param String $documentName
	 * @return DomDocument
	 */
	private function getTemplateDomDocumentForDocumentModel($documentModel, $moduleName = null, $documentName = null)
	{
		if(is_null($moduleName) && !is_null($documentModel))
		{
			$moduleName = $documentModel->getModuleName();
		}
		if(is_null($documentName) && !is_null($documentModel))
		{
			$documentName = $documentModel->getDocumentName();
		}

		$layoutDomDoc = $this->getLayoutDomDocForModel($documentModel, $moduleName, $documentName);

		// Create final DomDocument object
		$domDoc = new DOMDocument();
		$domDoc->preserveWhiteSpace = false;
		$domDoc->loadXML(
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<binding xmlns="'.self::NS_XBL.'">'
			. '<xbl:content xmlns="'.self::NS_XUL.'" xmlns:xbl="'.self::NS_XBL.'" xmlns:i18n="'.self::NS_I18N.'" xmlns:change="'.self::NS_CHANGE.'">'
			. '</xbl:content>'
			. '</binding>'
			);
		$rootNode = $domDoc->documentElement;
		$contentNode = $rootNode->firstChild;

		// Import layout nodes into final DomDocument object
		$layoutXPath = new DOMXPath($layoutDomDoc);
		$layoutXPath->registerNamespace('xul', self::NS_XUL);
		$nodeList = $layoutXPath->query('/xul:layout/*');
		for ($i = 0 ; $i < $nodeList->length ; $i++)
		{
			$contentNode->appendChild($domDoc->importNode($nodeList->item($i), true));
		}

		if ( ! is_null($documentModel) )
		{
			$this->replaceFieldsAndLabels($domDoc, $documentModel->getPropertiesInfos(), $documentModel);
		}

		// ----------

		// All the reamining <change:field /> elements must be removed from the form,
		// since they are hidden or they don't exist.
		$xpath = new DOMXPath($domDoc);
		$xpath->registerNamespace('change', self::NS_CHANGE);
		$fieldNodeList = $xpath->query('//change:field[@name]');
		for ($i=0 ; $i<$fieldNodeList->length ; $i++)
		{
			$fieldNode = $fieldNodeList->item($i);
			if ($fieldNode->parentNode->nodeName == 'row')
			{
				$fieldNode->parentNode->parentNode->removeChild($fieldNode->parentNode);
			}
		}

		// Remove any remaining <change:label /> elements.
		$labelNodeList = $xpath->query('//change:label[@field]');
		for ($i=0 ; $i<$labelNodeList->length ; $i++)
		{
			$fieldNode = $labelNodeList->item($i);
			$fieldNode->parentNode->removeChild($fieldNode);
		}

		// ----------

		// Load and merge implementation file if it exists.
		$implDomDoc = $this->getImplementationDomDocForModel($documentModel, $moduleName, $documentName);
		if ( ! is_null($implDomDoc) )
		{
			$xpath = new DOMXPath($implDomDoc);
			$implElm = $xpath->query('//implementation')->item(0);
			$rootNode->appendChild($domDoc->importNode($implElm, true));
			unset($implDomDoc);
		}

		// Generate a temporary file to store the domDoc's contents.
		// ".all.all.xul" is here for the template to find the file without any problem.
		$fileName = f_util_FileUtils::buildCachePath('FormBindingService_'.md5($moduleName.$documentName.time()).'.all.all.xul');
		f_util_FileUtils::write($fileName, str_replace("&amp;amp;", "&amp;", $domDoc->saveXML()), f_util_FileUtils::OVERRIDE);

		// Initialize template with this file: localize labels.
		$templateObject = new TemplateObject($fileName, K::XUL);

		if ( ! is_null($documentModel) )
		{
			foreach ($documentModel->getFormPropertiesInfos() as $name => $info)
			{
				$componentAttributes = array();
				$componentAttributes['control-type'] = $info->getControlType();
				$componentAttributes['label'] = f_Locale::translate($info->getLabel());
				$attributes[$name] = $componentAttributes;
			}
			$templateObject->setAttribute('attributes', $attributes);
		}

		// Reload the content of the template into the domDoc.
		$domDoc = new DOMDocument();
		$domDoc->preserveWhiteSpace = false;
		$domDoc->loadXML($templateObject->execute());

		// Delete temporary file.
		unlink($fileName);

		// Indent XML.
		$domDoc->normalizeDocument();

		return $domDoc;
	}


	private function replaceFieldsAndLabels($domDoc, $propertiesInfo, $documentModel = null)
	{
		// Search for <change:field/> and <change:label/> and replace them by the
		// final widget.
		$xpath = new DOMXPath($domDoc);
		$xpath->registerNamespace('change', self::NS_CHANGE);

		if (!is_null($documentModel))
		{
			$propertyInfo = $propertiesInfo['documentversion'];
			$formPropertyInfo = $documentModel->getFormProperty('documentversion');
			$docVersionNode = self::buildFieldNode($domDoc, $propertyInfo, $formPropertyInfo, $documentModel);
			$docVersionNode->setAttribute('hidden', 'true');
		}

		foreach ($propertiesInfo as $propertyInfo)
		{
			$name = $propertyInfo->getName();
			$formPropertyInfo = is_null($documentModel) ? null : $documentModel->getFormProperty($name);
			$hidden = false;
			
			if ($formPropertyInfo !== null && $formPropertyInfo->isHidden())
			{
				$hidden = true;
			}
						
			if (!$hidden)
			{
				$labelNodeList = $xpath->query('//change:label[@field="'.$name.'"]');
				if ($labelNodeList->length == 1)
				{
					$labelNode = $labelNodeList->item(0);
					$labelNode->parentNode->replaceChild(
						self::buildLabelNode($domDoc, $propertyInfo, $formPropertyInfo, $documentModel),
						$labelNode
						);
				}

				$fieldNodeList = $xpath->query('//change:field[@name="'.$name.'"]');
				if ($fieldNodeList->length == 1)
				{
					$fieldNode = $fieldNodeList->item(0);
					$newNode = self::buildFieldNode($domDoc, $propertyInfo, $formPropertyInfo, $documentModel);
					$fieldNode->parentNode->replaceChild($newNode, $fieldNode);
				}
			}
		}
	}


	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @param String $moduleName
	 * @param String $documentName
	 * @return DomDocument
	 */
	private function getImplementationDomDocForModel($documentModel, $moduleName = null, $documentName = null)
	{
		if(is_null($moduleName) && !is_null($documentModel))
		{
			$moduleName = $documentModel->getModuleName();
		}
		if(is_null($documentName) && !is_null($documentModel))
		{
			$documentName = $documentModel->getDocumentName();
		}

		// Load implementation definition if exists and import <implementation/> element.
		$implFile = Resolver::getInstance('file')
			->setPackageName('modules_' . $moduleName)
			->setDirectory('forms')
			->getPath($documentName . '_impl.js');
		if ( ! is_null($implFile) )
		{
			$converter = new uixul_JavaScriptToBindingConverter();
			$implDomDoc = new DOMDocument('1.0', 'utf-8');
			$implDomDoc->preserveWhiteSpace = false;
			$implDomDoc->loadXML('<?xml version="1.0"?><implementation>'.$converter->convert($implFile).'</implementation>');
			return $implDomDoc;
		}
		return null;
	}


	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @param String $moduleName
	 * @param String $documentName
	 * @return string
	 */
	public function generateFromModel($documentModel, $moduleName = null, $documentName = null)
	{
		if ( ! is_null($documentModel) )
		{
			if (is_null($moduleName))
			{
				$moduleName = $documentModel->getModuleName();
			}
			if (is_null($documentName))
			{
				$documentName = $documentModel->getDocumentName();
			}
			$documentIcon = $documentModel->getIcon();
		}
		else
		{
			if ($documentName == 'permission')
			{
				$documentIcon = 'shield_yellow';
			}
			else
			{
				$documentIcon = 'document';
			}
		}

		$domDoc = $this->getTemplateDomDocumentForDocumentModel($documentModel, $moduleName, $documentName);
		$this->addSurroundingFormElementsToDomDoc($domDoc, $moduleName, $documentName, $documentIcon);
		
		// Convert tabs to enable warning icon if on fiels is not valid.
		$this->fixTabs($domDoc);
				
		$domDoc->formatOutput = true;
		return $domDoc->saveXML();
	}

	/**
	 * Convert tabs to enable warning icon if on fiels is not valid.
	 * @param DOMNode $domDoc
	 */
	private function fixTabs($domDoc)
	{
		$tabs = $domDoc->getElementsByTagName('tab');
		foreach ($tabs as $tab)
		{
			if ($tab->hasAttribute('label'))
			{
				$labelNode = $domDoc->createElementNS(self::NS_XUL, 'label', $tab->getAttribute('label'));
				$tab->insertBefore($labelNode, $tab->firstChild);
			}
			if ($tab->hasAttribute('image'))
			{
				$imageNode = $domDoc->createElementNS(self::NS_XUL, 'image');
				$imageNode->setAttribute('src', $tab->getAttribute('image'));
				$tab->insertBefore($imageNode, $tab->firstChild);
			}
			$warningNode = $domDoc->createElementNS(self::NS_XUL, 'image');
			$warningNode->setAttribute('src', MediaHelper::getIcon('warning', 'small', null, MediaHelper::LAYOUT_SHADOW));
			$warningNode->setAttribute('collapsed', 'true');
			$tab->insertBefore($warningNode, $tab->firstChild);
		}
	}
	
	/**
	 * @param String $blockName
	 * @return string
	 */
	public function generateFromBlockName($blockName)
	{
		// $contentTemplateFile

		$domDoc = $this->getTemplateDomDocumentForBlockName($blockName);

		$domDoc->documentElement->setAttribute('id', 'wPropertyGrid');
		$domDoc->documentElement->setAttribute('extends', uixul_lib_BindingObject::getUrl('widgets.wPropertyGrid', false) . '#wPropertyGrid');

		$rootElm = $domDoc->createElement('xbl:bindings');
		//$rootElm->setAttribute('xmlns', 'http://www.mozilla.org/xbl');
		$rootElm->setAttributeNS(self::NS_XUL, 'xul:generation-date', date('Y-m-d H:i:s'));
		$rootElm->setAttributeNS(self::NS_XBL, 'xbl:generation-date', date('Y-m-d H:i:s'));
		$rootElm->appendChild($domDoc->documentElement);
		$domDoc->appendChild($rootElm);

		$domDoc->formatOutput = true;
		return $domDoc->saveXML();
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @return string
	 */
	private function generateFromModelForDeprecatedTemplates($documentModel, $moduleName = null, $documentName = null)
	{
		if ( ! is_null($documentModel) )
		{
			if (is_null($moduleName))
			{
				$moduleName = $documentModel->getModuleName();
			}
			if (is_null($documentName))
			{
				$documentName = $documentModel->getDocumentName();
			}
		}

		$formTemplateFile = Resolver::getInstance('file')
			->setPackageName('modules_' . $moduleName)
			->setDirectory('templates/forms')
			->getPath($documentName . '.all.all.xul');

		if ( is_null( $formTemplateFile ) )
		{
			throw new TemplateNotFoundException($documentModel->getName().': '.$formTemplateFile);
		}

		$templateObject = new TemplateObject($formTemplateFile, K::XUL);

		if ( ! is_null($documentModel) )
		{
			$attributes = array();
			foreach ($documentModel->getFormPropertiesInfos() as $name => $info)
			{
				$componentAttributes = array();
				$componentAttributes['control-type'] = $info->getControlType();
				$componentAttributes['label'] = f_Locale::translate($info->getLabel());
				$attributes[$name] = $componentAttributes;
			}
			$templateObject->setAttribute('attributes', $attributes);
		}

		$domDoc = new DOMDocument();
		$domDoc->preserveWhiteSpace = false;
		$domDoc->loadXML($templateObject->execute());
		return $domDoc;
	}


	private function addSurroundingFormElementsToDomDoc($domDoc, $moduleName, $documentName, $documentIcon = null)
	{
		$domDoc->documentElement->setAttribute('id', 'wForm-' . $moduleName . '-' . $documentName);
		$domDoc->documentElement->setAttribute('extends', uixul_lib_BindingObject::getUrl('form.wForm', false) . '#wForm');

		$contentElm = $this->XPathQuery($domDoc, '//xbl:content')->item(0);

		$formContentElm = $this->buildFormContentNode($domDoc, $contentElm);

		// main vbox
		$mainVboxElm = $domDoc->createElementNS(self::NS_XUL, 'vbox');
		$mainVboxElm->setAttribute('flex', '1');

		$headerElm = $this->buildHeaderNode(
			$domDoc,
			f_Locale::translate('&modules.'.$moduleName.'.document.'.$documentName.'.Edition-form-title;'),
			$documentIcon
			);
		$mainVboxElm->appendChild($headerElm);
		$contentElm->appendChild($mainVboxElm);

		$mainContentElm = $domDoc->createElementNS(self::NS_XUL, 'vbox');
		$mainContentElm->setAttribute('flex', '1');
		$mainContentElm->setAttribute('anonid', '_mainFormContent_');

		$deckElm = $this->buildDeckNode(
			$domDoc,
			array(
				$this->buildLoadingPanelNode($domDoc, $documentIcon),
				$formContentElm,
				$this->buildFormConfirmClosePanelNode($domDoc),
				$this->buildFormConfirmCreateNewPanelNode($domDoc),
				$this->buildFormConfirmLoadPanelNode($domDoc),
				$this->buildFormConfirmObsoletePanelNode($domDoc),
				$this->buildFormConfirmCreateNewFromPanelNode($domDoc)
			)
		);

		// <wformtoolbar anonid="mainToolbar"/>
		$wToolbarElm = $domDoc->createElement('wformtoolbar');
		$wToolbarElm->setAttribute('anonid', 'mainToolbar');
		$wToolbarElm->setAttributeNS (self::NS_XBL, 'xbl:inherits', 'collapsed=mainToolbarCollapsed');
		$mainContentElm->appendChild($wToolbarElm);

		$mainContentElm->appendChild($deckElm);
		$mainVboxElm->appendChild($mainContentElm);

		$rootElm = $domDoc->createElement('xbl:bindings');
		//$rootElm->setAttribute('xmlns', 'http://www.mozilla.org/xbl');
		$rootElm->setAttributeNS(self::NS_XUL, 'xul:generation-date', date('Y-m-d H:i:s'));
		$rootElm->setAttributeNS(self::NS_XBL, 'xbl:generation-date', date('Y-m-d H:i:s'));

		$rootElm->appendChild($domDoc->documentElement);
		$domDoc->appendChild($rootElm);
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param string $label
	 * @param string $icon
	 */
	private function buildHeaderNode($domDoc, $label, $icon)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Header');
		$templateObject->setAttribute('src', MediaHelper::getIcon($icon, MediaHelper::COMMAND, null, MediaHelper::LAYOUT_SHADOW));
		$templateObject->setAttribute('label', $label);
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param string $icon
	 */
	private function buildLoadingPanelNode($domDoc, $icon)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Loading-Panel');
		$templateObject->setAttribute('icon', MediaHelper::getIcon($icon, MediaHelper::BIG, null, MediaHelper::LAYOUT_SHADOW));
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param array<DOMNode> $childNodes
	 */
	private function buildDeckNode($domDoc, $childNodes = null)
	{
		$deckElm = $domDoc->createElementNS(self::NS_XUL, 'deck');
		$deckElm->setAttributeNS(self::NS_XBL, 'xbl:inherits', 'flex');
		$deckElm->setAttribute('flex', '1');
		$deckElm->setAttribute('anonid', '_deck_');
		$deckElm = $domDoc->importNode($deckElm, true);
		if (is_array($childNodes))
		{
			foreach ($childNodes as $childNode)
			{
				if ($childNode instanceof DOMNode)
				{
					$deckElm->appendChild($domDoc->importNode($childNode));
				}
			}
		}
		return $deckElm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param DOMElement $contentElm
	 */
	private function buildFormContentNode($domDoc, $contentElm)
	{
		$vboxElm = $domDoc->createElementNS(self::NS_XUL, 'vbox');
		$vboxElm->setAttribute('flex', '3');

		foreach ($contentElm->childNodes as $childNode)
		{
			$vboxElm->appendChild($domDoc->importNode($childNode, true));
		}

		$suggestionPannelElm = $domDoc->createElementNS(self::NS_XUL, 'wsuggestionpannel');
		$suggestionPannelElm->setAttribute('flex', '1');
		$suggestionPannelElm->setAttribute('anonid', 'suggestionPannel');
		$suggestionPannelElm->setAttribute('class', 'suggestionPannel');
		$suggestionPannelElm->setAttribute('collapsed', 'true');

		$vboxElm->appendChild($domDoc->importNode($suggestionPannelElm, true));

		$hboxElm = $domDoc->createElementNS(self::NS_XUL, 'hbox');
		$hboxElm->setAttribute('flex', '1');
		$helpPanel = $domDoc->createElementNS(self::NS_XUL, 'wformhelppanel');
		$helpPanel->setAttribute('width', '200px');
		$helpPanel->setAttribute('anonid', 'helpPanel');
		$helpPanel->setAttributeNS (self::NS_XBL, 'xbl:inherits', 'collapsed=mainToolbarCollapsed');
		$hboxElm->appendChild($helpPanel);
		$hboxElm->appendChild($vboxElm);

		return $hboxElm;
	}


	/**
	 * @param DOMDocument $domDoc
	 */
	private function buildFormConfirmClosePanelNode($domDoc)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Confirm-Close-Panel');
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}


	/**
	 * @param DOMDocument $domDoc
	 */
	private function buildFormConfirmCreateNewPanelNode($domDoc)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Confirm-Createnew-Panel');
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}

	
	/**
	 * @param DOMDocument $domDoc
	 */
	private function buildFormConfirmCreateNewFromPanelNode($domDoc)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Confirm-Createnewfrom-Panel');
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}


	/**
	 * @param DOMDocument $domDoc
	 */
	private function buildFormConfirmLoadPanelNode($domDoc)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Confirm-Load-Panel');
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}


	/**
	 * @param DOMDocument $domDoc
	 */
	private function buildFormConfirmObsoletePanelNode($domDoc)
	{
		$templateObject = TemplateLoader::getInstance()
			->setPackageName('modules_uixul')
			->setDirectory('templates')
			->setMimeContentType(K::XUL)
			->load('Uixul-Form-Confirm-Obsolete-Panel');
		$localDomDoc = new DOMDocument();
		$localDomDoc->preserveWhiteSpace = false;
		$localDomDoc->loadXML($templateObject->execute());
		return $domDoc->importNode($localDomDoc->documentElement, true);
	}

	private static function completeFieldNodeFromDocumentModel($elm, $domDoc, $documentModel, $propertyInfo)
	{
		$name = $elm->getAttribute('name');
		$localeKey = null;
		if ( ! is_null($documentModel) )
		{
			$elm->setAttributeNS(self::NS_I18N, 'i18n:attributes', 'label &modules.'.$documentModel->getModuleName().'.document.'.$documentModel->getDocumentName().'.'.ucfirst($name).';');
			$localeKey = '&modules.'.$documentModel->getModuleName().'.document.'.$documentModel->getDocumentName().'.'.ucfirst($name).'-help;';
		}
		else if ($propertyInfo instanceof block_BlockPropertyInfo)
		{
			$elm->setAttribute('label', f_Locale::translate($propertyInfo->getLabel()));
			$localeKey = $propertyInfo->getHelpText();
		}
		if ( ! is_null($localeKey) )
		{
			$wHelpElm = $domDoc->createElement('whelp', f_Locale::translate($localeKey));
			$elm->appendChild($wHelpElm);
		}
	}

	/**
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return uixul_ControlInfo
	 */
	private static function getControlInfo($propertyInfo, $formPropertyInfo)
	{
		$controlInfo = new uixul_ControlInfo();

		if ( ! is_null($formPropertyInfo) )
		{
			$controlInfo->controlType = $formPropertyInfo->getControlType();
			$controlInfo->readOnly = $formPropertyInfo->isReadonly();
			$controlInfo->editOnce = $formPropertyInfo->isEditOnce();
			$controlInfo->required = $formPropertyInfo->isRequired();
		}
		else
		{
			switch ($propertyInfo->getType())
			{
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER :
					$controlInfo->controlType = 'number';
					break;

				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN :
					$controlInfo->controlType = 'boolean';
					break;

				default:
					if ($propertyInfo instanceof block_BlockPropertyInfo && $propertyInfo->hasListId())
					{
						$controlInfo->controlType = 'list';
						$controlInfo->attributes['list-id'] = $propertyInfo->getListId();
					}
					else
					{
						$controlInfo->controlType = $propertyInfo->isDocument() ? 'picker' : 'text';
					}
					break;
			}
			$controlInfo->readOnly = false;
			$controlInfo->editOnce = false;
			$controlInfo->required = ($propertyInfo instanceof block_BlockPropertyInfo) && $propertyInfo->isRequired();
		}

		return $controlInfo;
	}

	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @param $documentModel f_persistentdocument_PersistentDocumentModel
	 * @return DOMElement
	 */
	public static function buildFieldNode($domDoc, $propertyInfo, $formPropertyInfo, $documentModel = null)
	{
		$name = $propertyInfo->getName();
		$controlInfo = self::getControlInfo($propertyInfo, $formPropertyInfo);

		$controlTypes = self::getAvailableControlTypes();
		$controlType = strtolower($controlInfo->controlType);
		if ( ! in_array($controlType, $controlTypes) )
		{
			throw new Exception("Don't know how to handle a control of type '".$controlType."'. Must be one of the following: ".join(", ", $controlTypes).".");
		}
		$methodName = 'build'.ucfirst($controlInfo->controlType).'WidgetNode';
		if ($formPropertyInfo === null && f_util_ClassUtils::methodExists($propertyInfo, 'getFormPropertyInfo'))
		{
			$formPropertyInfo = $propertyInfo->getFormPropertyInfo();
		}
		$elm = f_util_ClassUtils::callMethodArgs(
			get_class(),
			$methodName,
			array($domDoc, $propertyInfo, $formPropertyInfo)
			);

		$elm->setAttribute('name', $name);
		if ($controlInfo->readOnly)
		{
			$elm->setAttribute('readonly', 'true');
		}
		if ($controlInfo->editOnce)
		{
			$elm->setAttribute('editonce', 'true');
		}

		if ($propertyInfo->getDefaultValue())
		{
			$elm->setAttribute('default-value', f_Locale::translate($propertyInfo->getDefaultValue()));
		}

		// Add all other attributes found in the formPropertyInfo object.
		$attributes = $controlInfo->attributes;
		if ( ! is_null($formPropertyInfo) )
		{
			$attributes = array_merge($attributes, $formPropertyInfo->getAttributes());
		}
		foreach ($attributes as $attrName => $attrValue)
		{
			if ( ! $elm->hasAttribute($attrName) )
			{
				$elm->setAttribute($attrName, $attrValue);
			}
		}

		// Add field's constraints
		$constraintsParser = new validation_ContraintsParser();
		$constraintArray = $constraintsParser->getConstraintArrayFromDefinition(
			$propertyInfo->getConstraints()
			);
		if ($controlInfo->required)
		{
			$constraintArray['blank'] = 'false';
			$elm->setAttribute('required', 'true');
		}

		$addedConstraints = array();

		// intcours - 2007-07-09 - some constraints may already be set at this point,
		// by the specific buildXXXWidgetNode method, so we have to fill the
		// $addedConstraints array with their name in order to avoid duplicates
		// (because duplicates could break the form) :
		$existingConstraints = $elm->getElementsByTagName('constraint');
		foreach ($existingConstraints as $existingConstraint)
		{
			$addedConstraints[] = $existingConstraint->getAttribute('name');
		}

		foreach ($constraintArray as $name => $value)
		{
			if ( ! in_array($name, $addedConstraints) )
			{
				$constraintElm = $domDoc->createElement('constraint', $value);
				$constraintElm->setAttribute('name', $name);
				$elm->appendChild($constraintElm);
				$addedConstraints[] = $name;
			}
		}

		self::completeFieldNodeFromDocumentModel($elm, $domDoc, $documentModel, $propertyInfo);

		return $elm;
	}


	/**
	 * Returns the control-types available for the auto-form-generation.
	 *
	 * @return array<string>
	 */
	public static function getAvailableControlTypes()
	{
		$controlTypes = array();
		$class = new ReflectionClass(__CLASS__);
		$methods = $class->getMethods();
		foreach ($methods as $method)
		{
			if (preg_match('/^build([A-Z][a-z]+)WidgetNode$/', $method->getName(), $matches))
			{
				$controlTypes[] = strtolower($matches[1]);
			}
		}
		return $controlTypes;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildTextWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		if (!is_null($formPropertyInfo))
		{
			$attributes = $formPropertyInfo->getAttributes();
		}
		else
		{
			$attributes = array();
		}
		if ((array_key_exists('rich', $attributes) && $attributes['rich'] == 'true')
			|| $propertyInfo->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
		{
			$elm = $domDoc->createElementNS(self::NS_XUL, 'wrichtext');
		}
		else
		{
			$elm = $domDoc->createElementNS(self::NS_XUL, 'wtextbox');
			if ($propertyInfo->getType() != f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING)
			{
				if (array_key_exists('multiline', $attributes))
				{
					$elm->setAttribute('multiline', $attributes['multiline']);
				}
				else
				{
					$elm->setAttribute('multiline', 'true');
				}
			}
		}

		return $elm;
	}
	
	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildCodeWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wcodeeditor');
		$propertyAttrs = $formPropertyInfo->getAttributes();
		
		$elm->setAttribute("cols", isset($propertyAttrs["cols"]) ? $propertyAttrs["cols"] : "80"); 
		$elm->setAttribute("rows", isset($propertyAttrs["rows"]) ? $propertyAttrs["rows"] : "20");
		$elm->setAttribute("syntax", isset($propertyAttrs["syntax"]) ? $propertyAttrs["syntax"] : "php");
        return $elm;
	}

	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildFilepickerWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
        $elm = $domDoc->createElementNS(self::NS_XUL, 'wfilepicker');
		if ($propertyInfo->getMaxOccurs() == 1)
		{
			$elm->setAttribute('seltype', 'single');
			$elm->setAttribute('rows', '1');
		}
		
		if ($formPropertyInfo !== null)
		{
			$attr = $formPropertyInfo->getAttributes();
			if (!isset($attr['width']))
			{
				$elm->setAttribute('width', '300');
			}
		}

		$orderable = ($propertyInfo->getMaxOccurs() != 1);
		$allow = false;
		$drop = 'application/x-moz-file';
		if ($formPropertyInfo !== null)
		{
			$attr = $formPropertyInfo->getAttributes();
			if (isset($attr['candropmedia']))
			{
				$drop .= ' listitem/component';
				$allow = $propertyInfo->getTypeForBackofficeWidgets();
			}		
			if (isset($attr['orderable']))
			{
				$orderable = false;
			}
		}
		if ($orderable) {$elm->setAttribute('orderable', 'true');}
		
		$elm->setAttribute('candrop', $drop);
		if ($allow) {$elm->setAttribute('allow', $allow);}
		
		$elm->setAttribute('maxfile', $propertyInfo->getMaxOccurs());
		$elm->setAttribute('minfile', $propertyInfo->getMinOccurs());		
        return $elm;
	}

	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildNumberWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wtextbox');
		$constraint = $domDoc->createElement('constraint', 'true');
		if ($propertyInfo->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER)
		{
			$constraint->setAttribute('name', 'integer');
		}
		else
		{
			$constraint->setAttribute('name', 'float');
		}
		$elm->setAttribute('size', '5');
		$elm->setAttribute('maxlength', '10');
		$elm->appendChild($constraint);
		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildListWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		if ($propertyInfo->isArray())
		{
			$elm = $domDoc->createElementNS(self::NS_XUL, 'wlistbox');
		}
		else
		{
			$elm = $domDoc->createElementNS(self::NS_XUL, 'wcombobox');
		}

		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildPickerWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'welementpicker');
		$elm->setAttribute('type', 'droppable');
		if (!$propertyInfo->isArray() || $propertyInfo->getMaxOccurs() == 1)
		{
			$elm->setAttribute('seltype', 'single');
			$elm->setAttribute('rows', '1');
		}
			
		if ($formPropertyInfo !== null)
		{
			$attr = $formPropertyInfo->getAttributes();
			if (!isset($attr['width']))
			{
				$elm->setAttribute('width', '300');
			}
		}
		
		if ($propertyInfo->isDocument())
		{
			$allowedType = $propertyInfo->getType();
			if (!strcasecmp($allowedType, f_persistentdocument_PersistentDocumentModel::BASE_MODEL))
			{
				$allowedType = '*';
			}
			else
			{
				// Intportg - 29/05/2008 : in case of property grid, there is no $formPropertyInfo.
				$tmp = (!is_null($formPropertyInfo)) ? $formPropertyInfo->getAttributes() : array();
				if (isset($tmp['allow']))
				{
					// TODO : manage this in model compilation ?
					// Models are separated by somtimes spaces, somtimes comas, somtimes spaces and comas...
					$allowedTypes = split('[^a-zA-Z0-9_/]+', $tmp['allow']);
					foreach ($allowedTypes as $key => $value)
					{
						// Models are sometimes formatted like modules_list_staticlist and not modules_list/staticlist.
						if (strpos($value, '/') === false)
						{
							$allowedTypes[$key] = substr_replace($value, '/', strrpos($value, '_'), 1);
						}
					}
				}
				else
				{
					$allowedTypes = array($allowedType);
				}

				// Intportg - 14/05/2008 : manage the case of multiple allowed types.
				$finalAllowedTypes = array();
				foreach ($allowedTypes as $singleAllowedType)
				{
					try
					{
						$allowedModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($singleAllowedType);
						$modelName = $allowedModel->getBackofficeName();
						$finalAllowedTypes[] = $modelName;
						$elm->setAttribute('label-for-'.$modelName, f_Locale::translate($allowedModel->getLabel()));
					}
					catch(Exception $e)
					{
						Framework::exception($e);
					}
				}
				$allowedType = implode(' ', $finalAllowedTypes);
			}
			$elm->setAttribute('allow', $allowedType);
			$elm->setAttribute('allowed-statuses', '*');
		}

		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildColorWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wcolorpicker');
		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildSizeWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wsizebox');
		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildDateWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wdatepicker');
		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildBooleanWidgetNode($domDoc, $propertyInfo, $formPropertyInfo)
	{
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wboolean');
		return $elm;
	}


	/**
	 * @param DOMDocument $domDoc
	 * @param PropertyInfo $propertyInfo
	 * @param FormPropertyInfo $formPropertyInfo
	 * @return DOMElement
	 */
	public static function buildLabelNode($domDoc, $propertyInfo, $formPropertyInfo, $documentModel)
	{
	    $required = false;
		if ($propertyInfo->getMinOccurs() == 1 || ($formPropertyInfo && $formPropertyInfo->isRequired()))
		{
			$required = true;
		}
		if (!is_null($documentModel))
		{
			$content = '&modules.'.$documentModel->getModuleName().'.document.'.$documentModel->getDocumentName().'.'.ucfirst($propertyInfo->getName()).';';
		}
		else if ($propertyInfo instanceof block_BlockPropertyInfo)
		{
			$content = $propertyInfo->getLabel();
		}
		else
		{
			$content = $propertyInfo->getName();
		}
		$elm = $domDoc->createElementNS(self::NS_XUL, 'wlabel', f_Locale::translate($content));
		if ($required)
		{
			$elm->setAttribute('class', 'required');
			$elm->setAttribute('indicator', '*');

			// Disposition is different when in a property grid.
		    if ( $propertyInfo instanceof block_BlockPropertyInfo )
		    {
		    	$elm->setAttribute('indicator-position', 'after');
		    }
		}
		else if ( ! $propertyInfo instanceof block_BlockPropertyInfo )
		{
		    $elm->setAttribute('indicator', ' ');
		}
		$elm->setAttribute('anonid', 'label_' . $propertyInfo->getName());

		return $elm;
	}
}

class uixul_ControlInfo
{
	/**
	 * @var String
	 */
	public $controlType;

	/**
	 * @var Boolean
	 */
	public $readOnly;

	/**
	 * @var Boolean
	 */
	public $required;

	/**
	 * @var Boolean
	 */
	public $editOnce;

	/**
	 * @var Array
	 */
	public $attributes = array();
}