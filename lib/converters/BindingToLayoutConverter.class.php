<?php
/**
 * Converts a XUL binding file (<xbl:content/>) into a Layout file.
 */
class uixul_BindingToLayoutConverter implements uixul_Converter
{
	/**
	 * Converts the binding file $bindingFile to a Layout contents and
	 * returns it as a string.
	 *
	 * @param string $bindingFile
	 * @return string
	 */
	public function convert($bindingFile)
	{
		$domDoc = new DOMDocument();
		$domDoc->preserveWhiteSpace = false;
		if ( ! $domDoc->loadXML($this->getBindingContents($bindingFile)) )
		{
			throw new Exception("Unable to open XML file: ".$bindingFile.".");
		}
		$xpath = new DOMXPath($domDoc);
		$xpath->registerNamespace('xul', uixul_BaseBindingService::NS_XUL);
		$xpath->registerNamespace('xbl', uixul_BaseBindingService::NS_XBL);
		$xpath->registerNamespace('tal', uixul_BaseBindingService::NS_TAL);
		$xpath->registerNamespace('i18n', uixul_BaseBindingService::NS_I18N);
		$nodeList = $xpath->query('//xbl:content');

		if ($nodeList->length == 0)
		{
			throw new Exception("Unable to find a <content/> element.");
		}

		$contentNode = $nodeList->item(0);

		$layoutDomDoc = new DOMDocument('1.0', 'utf-8');
		$domDoc->preserveWhiteSpace = false;
		$layoutDomDoc->loadXML('<?xml version="1.0" encoding="utf-8"?><layout
	xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:i18n="http://phptal.motion-twin.com/i18n"
	xmlns:tal="http://phptal.motion-twin.com/tal"
	xmlns:change="http://www.rbs-change.eu/change-4.2/taglib/phptal" />');
		for ($i=0 ; $i < $contentNode->childNodes->length ; $i++)
		{
			$layoutDomDoc->documentElement->appendChild($layoutDomDoc->importNode($contentNode->childNodes->item($i), true));
		}

		$xpath = new DOMXPath($layoutDomDoc);
		$xpath->registerNamespace('xul', uixul_BaseBindingService::NS_XUL);
		$xpath->registerNamespace('xbl', uixul_BaseBindingService::NS_XBL);
		$xpath->registerNamespace('tal', uixul_BaseBindingService::NS_TAL);
		$xpath->registerNamespace('i18n', uixul_BaseBindingService::NS_I18N);

		// convert <w*/> fields to <change:field/> elements.
		$nodeList = $xpath->query('//xul:*');
		for ($i = 0 ; $i < $nodeList->length ; $i++)
		{
			$fieldName = null;
			$node = $nodeList->item($i);
			if (($node->nodeName == 'label' || $node->nodeName == 'wlabel'))
			{
				if ($node->hasAttribute('content'))
				{
					list(, $fieldName,) = explode('/', $node->getAttribute('content'));
				}
				else if ($node->hasAttribute('attributes'))
				{
					//  attributes="value &amp;modules.website.document.page.TitleLabel;"
					if (preg_match('/^value\s+&modules\.[a-z]+\.document\.[a-z]+\.([a-zA-Z0-9\-]+);$/', $node->getAttribute('attributes'), $matches))
					{
						if (f_util_StringUtils::endsWith($matches[1], 'Label', true))
						{
							$fieldName = strtolower(substr($matches[1], 0, -strlen('Label')));
						}
					}
				}
				if (!is_null($fieldName))
				{
					$newLabelNode = $layoutDomDoc->createElementNS(uixul_BaseBindingService::NS_CHANGE, 'change:label');
					$newLabelNode->setAttribute('field', $fieldName);
					$node->parentNode->replaceChild($newLabelNode, $node);
				}
			}
			// remove wformtoolbar, but look for special attributes on it:
			// hidesubmit, hidecreate, hidereset
			else if ($node->nodeName{0} == 'wformtoolbar')
			{
				$onInitJs = array();
				if ($node->getAttribute('hidecreate'))
				{
					$onInitJs[] = "this.toolbar.hideCreateButton();";
				}
				if ($node->getAttribute('hidesubmit'))
				{
					$onInitJs[] = "this.toolbar.hideSubmitButton();";
				}
				if ($node->getAttribute('hidereset'))
				{
					$onInitJs[] = "this.toolbar.hideResetButton();";
				}
				$node->parentNode->removeChild($node);
				if ( !empty($onInitJs) )
				{
					echo "wFormToolbar held some useful information that this script can't handle automatically.\n";
					echo "Please add the following lines in the onInit() function in the JS file:\n";
					echo "><8 ".str_repeat('-', 70) . "\n";
					echo join("\n", $onInitJs) . "\n";
					echo "><8 ".str_repeat('-', 70) . "\n";
				}
			}
			// remove wformheader
			else if ($node->nodeName{0} == 'wformheader')
			{
				$node->parentNode->removeChild($node);
			}
			else if ($node->nodeName{0} == 'w' && ($node->hasAttribute('field-name') || $node->hasAttribute('name')) )
			{

				if ($node->hasAttribute('field-name'))
				{
					$fieldName = $node->getAttribute('field-name');
				}
				else if ($node->hasAttribute('name'))
				{
					$fieldName = $node->getAttribute('name');
				}
				$newFieldNode = $layoutDomDoc->createElementNS(uixul_BaseBindingService::NS_CHANGE, 'change:field');
				$newFieldNode->setAttribute('name', $fieldName);
				$node->parentNode->replaceChild($newFieldNode, $node);
			}
			else
			{
				$attrToTranslate = array();
				$nodeMap = $node->attributes;
				for ($j = 0 ; $j < $nodeMap->length ; $j++)
				{
					$domAttr = $nodeMap->item($j);
					if (f_Locale::isLocaleKey($domAttr->value))
					{
						$attrToTranslate[] = $domAttr->name.' '.str_replace('&', '&amp;', $domAttr->value);
						$node->removeAttributeNode($domAttr);
					}
				}
				if (!empty($attrToTranslate))
				{
					$node->setAttributeNS(uixul_BaseBindingService::NS_I18N, 'attributes', join("; ", $attrToTranslate));
				}
			}
		}

		$layoutDomDoc->formatOutput = true;
		return $layoutDomDoc->saveXML();
	}


	private function getBindingContents($bindingFile)
	{
		$contents = str_replace('&', '&amp;', file_get_contents($bindingFile));
		if (substr($contents, 0, 5) != '<?xml')
		{
			// Add root element and missing XML namespaces...
			$contents =
				'<?xml version="1.0"?>'
				. '<root'
				. ' xmlns="http://www.mozilla.org/xbl"'
				. ' xmlns:tal="http://phptal.motion-twin.com/tal"'
				. ' xmlns:i18n="http://phptal.motion-twin.com/i18n"'
				. ' xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"'
				. ' xmlns:xbl="http://www.mozilla.org/xbl"'
				. ' xmlns:change="http://www.rbs-change.eu/change-4.2/taglib/phptal">'
				. $contents
				. '</root>';
		}

		// Add missing XML namespaces... OK, this is dirty but this is
		// functionnal for our needs.
		$p1 = strpos($contents, '<xbl:content');
		$p2 = strpos($contents, '>', $p1+1);
		$sub = substr($contents, $p1, $p2-$p1);
		if (!strpos($sub, 'xmlns:i18n'))
		{
			$contents = str_replace('<xbl:content', '<xbl:content xmlns:i18n="http://phptal.motion-twin.com/i18n"', $contents);
		}
		if (!strpos($sub, 'xmlns:tal'))
		{
			$contents = str_replace('<xbl:content', '<xbl:content xmlns:tal="http://phptal.motion-twin.com/tal"', $contents);
		}




		return $contents;
	}

}