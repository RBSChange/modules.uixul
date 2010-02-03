<?php
include_once 'beautifier.php';

/**
 * Converts a XUL binding file (<implementation/>) into a JavaScript file.
 */
class uixul_BindingToJavaScriptConverter implements uixul_Converter
{
	private $events      = array();
	private $knownEvents = array('input', 'change', 'focus', 'blur', 'click', 'command');
	private $foundMembers;


	/**
	 * Converts the binding file $bindingFile to a JavaScript contents and
	 * returns it as a string.
	 *
	 * @param string $bindingFile
	 * @return string
	 */
	public function convert($bindingFile, $documentModel = null)
	{
		$js = "";
		$domDoc = new DOMDocument();
		$domDoc->preserveWhiteSpace = false;
		$content = trim(str_replace('&', '&amp;', file_get_contents($bindingFile)));
		if (substr($content, 0, 5) != '<?xml')
		{
			$content =
				'<?xml version="1.0"?>'
				. '<root'
				. ' xmlns="http://www.mozilla.org/xbl"'
				. ' xmlns:tal="http://phptal.motion-twin.com/tal"'
				. ' xmlns:i18n="http://phptal.motion-twin.com/i18n"'
				. ' xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"'
				. ' xmlns:xbl="http://www.mozilla.org/xbl"'
				. ' xmlns:change="http://www.rbs-change.eu/change-4.2/taglib/phptal">'
				. $content
				. '</root>';
		}

		if ( ! $domDoc->loadXML($content) )
		{
			throw new Exception("Unable to open XML file: ".$bindingFile." (not well-formed?).");
		}
		$xpath = new DOMXPath($domDoc);
		$xpath->registerNamespace('xul', uixul_BaseBindingService::NS_XUL);
		$xpath->registerNamespace('xbl', uixul_BaseBindingService::NS_XBL);
		$nodeList = $xpath->query('//xbl:implementation');

		if ($nodeList->length == 0)
		{
			throw new Exception("Unable to find an <implementation/> element.");
		}

		$onInitMethod = null;
		$this->foundMembers = array();
		$implNode = $nodeList->item(0);
		for ($i=0 ; $i < $implNode->childNodes->length ; $i++)
		{
			if ($implNode->childNodes->item($i)->nodeName == 'method')
			{
				$methodNode = $implNode->childNodes->item($i);
				$parameterNodes = $xpath->query('xbl:parameter', $methodNode);
				$parameters = array();
				for ($p = 0 ; $p < $parameterNodes->length ; $p++)
				{
					$param = new uixul_BindingMethodParameter();
					$param->name = $parameterNodes->item($p)->getAttribute('name');
					if ($parameterNodes->item($p)->hasAttribute('doc-text'))
					{
						$param->description = $parameterNodes->item($p)->getAttribute('doc-text');
					}
					if ($parameterNodes->item($p)->hasAttribute('doc-type'))
					{
						$param->type = $parameterNodes->item($p)->getAttribute('doc-type');
					}
					$parameters[] = $param;
				}
				$method = new uixul_BindingMethod();
				$method->name = $methodNode->getAttribute('name');
				$method->body = str_replace('&amp;&amp;', '&&', $xpath->query('xbl:body', $methodNode)->item(0)->firstChild->nodeValue);
				$method->parameters = $parameters;
				if ($methodNode->hasAttribute('doc-text'))
				{
					$method->description = $methodNode->getAttribute('doc-text');
				}
				if ($methodNode->hasAttribute('doc-type'))
				{
					$method->returnType = $methodNode->getAttribute('doc-type');
				}
				if ($method->name == 'onInit')
				{
					$onInitMethod = $method;
				}
				else
				{
					$js .= $method->getJS();
				}

				$this->foundMembers[] = $method->name;
			}
			else if ($implNode->childNodes->item($i)->nodeName == 'property')
			{
				$propertyNode = $implNode->childNodes->item($i);
				$getter = null;
				if ($propertyNode->hasAttribute('onget'))
				{
					$getter = trim($propertyNode->getAttribute('onget'));
					if ($getter{strlen($getter)-1} != ';')
					{
						$getter .= ';';
					}
				}
				else
				{
					$nodeList = $xpath->query('xbl:getter', $propertyNode);
					if ($nodeList->length == 1)
					{
						$getter = $nodeList->item(0)->firstChild->nodeValue;
					}
				}
				if (!is_null($getter))
				{
					$property = new uixul_BindingProperty();
					$property->name = $propertyNode->getAttribute('name');
					$property->readonly = $propertyNode->getAttribute('readonly');
					$property->type = $propertyNode->getAttribute('doc-type');
					$property->description = $propertyNode->getAttribute('doc-text');
					$property->body = $getter;
					$property->getterOrSetter = 'getter';
					$js .= $property->getJS();
				}

				$setter = null;
				if ($propertyNode->hasAttribute('onset'))
				{
					$setter = trim($propertyNode->getAttribute('onset'));
					if ($setter{strlen($setter)-1} != ';')
					{
						$setter .= ';';
					}
				}
				else
				{
					$nodeList = $xpath->query('xbl:setter', $propertyNode);
					if ($nodeList->length == 1)
					{
						$setter = $nodeList->item(0)->firstChild->nodeValue;
					}
				}
				if (!is_null($setter))
				{
					$property = new uixul_BindingProperty();
					$property->name = $propertyNode->getAttribute('name');
					$property->readonly = $propertyNode->getAttribute('readonly');
					$property->type = $propertyNode->getAttribute('doc-type');
					$property->description = $propertyNode->getAttribute('doc-text');
					$property->body = $setter;
					$property->getterOrSetter = 'setter';
					$js .= $property->getJS();
				}

				$this->foundMembers[] = $propertyNode->getAttribute('name');
			}
			else if ($implNode->childNodes->item($i)->nodeName == 'field')
			{
				$fieldNode = $implNode->childNodes->item($i);
				$field = new uixul_BindingField();
				$field->name = $fieldNode->getAttribute('name');
				$field->readonly = $fieldNode->getAttribute('readonly');
				$field->type = $fieldNode->getAttribute('doc-type');
				$field->description = $fieldNode->getAttribute('doc-text');
				$field->value = $fieldNode->firstChild->nodeValue;
				$js .= $field->getJS();

				$this->foundMembers[] = $field->name;
			}
		}


		// Look for 'on*' attributes on fields
		$this->events = array();
		$nodeList = $xpath->query('//xbl:content//descendant::*[@*]');
		for ($i = 0 ; $i < $nodeList->length ; $i++)
		{
			$node = $nodeList->item($i);
			if ($node->nodeName{0} == 'w' && ($node->hasAttribute('field-name') || $node->hasAttribute('name')) )
			{
				$fieldName = null;
				if ($node->hasAttribute('field-name'))
				{
					$fieldName = $node->getAttribute('field-name');
				}
				else if ($node->hasAttribute('name'))
				{
					$fieldName = $node->getAttribute('name');
				}
				if ( ! is_null($fieldName) )
				{
					foreach ($this->knownEvents as $event)
					{
						if ($node->hasAttribute('on'.$event))
						{
							$this->events[$fieldName][$event] = $node->getAttribute('on'.$event);
						}
					}
				}
			}
		}

		if ( ! empty($this->events) )
		{
			$initEventJs = '';
			foreach ($this->events as $fieldName => $events)
			{
				foreach ($events as $eventName => $code)
				{
					$initEventJs .= $this->buildEventHandler($fieldName, $eventName, $code)."\n";
				}
			}

			if (is_null($onInitMethod))
			{
				$onInitMethod = new uixul_BindingMethod();
				$onInitMethod->name = 'onInit';
			}
			$onInitMethod->body .= $initEventJs;
		}


		if (!is_null($onInitMethod))
		{
			$js .= $onInitMethod->getJS();
		}

		$smarty = new builder_Generator();
		// Assign all necessary variable
		$smarty->assign_by_ref('content', $js);
		$smarty->assign_by_ref('date', date(DATE_RFC822));
		if ($documentModel)
		{
			$smarty->assign_by_ref('documentModel', $documentModel);
		}
		$smarty->assign_by_ref('generatedFrom', $bindingFile);

		// Execute template and return result
		return $smarty->fetch(dirname(__FILE__) .'/impl.js.tpl');
	}


	private function buildEventHandler($fieldName, $eventName, $code)
	{
		if (preg_match_all('#^\s*([a-zA-Z0-9_]+)\s*[\)\(=\+\-/]#m', $code, $matches))
		{
			foreach ($matches[1] as $member)
			{
				if ( in_array($member, $this->foundMembers) )
				{
					$code = str_replace($member, 'this.form.'.$member, $code);
				}
			}
		}
		$handlerName = $fieldName.'_'.$eventName.'_handler';
		$js =
			'var '.$handlerName.' =' . "\n"
			. "{\n"
			. "\tform: this,\n"
			. "\thandleEvent: function(event)\n"
			. "\t{\n"
			. uixul_JavaScriptToBindingConverter::indent($code, 2)."\n"
			. "\t}\n"
			. "}\n"
			. "this.getFieldByName('$fieldName').addEventListener('$eventName', $handlerName, false);\n";
		return $js;
	}
}

/**
 * Represents a XUL binding method.
 */
class uixul_BindingMethod
{
	public
		$name,
		$description,
		$parameters,
		$body,
		$returnType;

	public function generateHeader()
	{
		$js = "/**\n * ".$this->name."\n * ".$this->description;
		foreach ($this->parameters as $parameter)
		{
			$js .= $parameter->generateDocLine();
		}
		if ($this->returnType)
		{
			$js .= "\n * @return ".$this->returnType;
		}
		$js .= "\n */\n";
		return $js;
	}

	public function generateBody()
	{
		$js = "function ".$this->name."(";
		$nbp = count($this->parameters);
		for ($i=0 ; $i < $nbp ; $i++)
		{
			if ($i > 0)
			{
				$js .= ", ";
			}
			$js .= $this->parameters[$i]->name;
		}
		$js .= ")\n{\n".$this->body."\n}";
		ob_start();
		js_beautify($js);
		$js = ob_get_clean();
		$js = preg_replace('/{\s*K\s*::\s*([A-Z_]+)\s*}/m', '{K::$1}', $js);
		return $js."\n\n\n";
	}

	public function getJS()
	{
		return $this->generateHeader().$this->generateBody();
	}
}


/**
 * Represents a XUL binding method parameter.
 */
class uixul_BindingMethodParameter
{
	public
		$name,
		$type,
		$description;

	public function generateDocLine()
	{
		return "\n ".trim("* @param ".($this->type?$this->type:'object')." ".$this->name." ".$this->description);
	}
}



/**
 * Represents a XUL binding field.
 */
class uixul_BindingField
{
	public
		$name,
		$description,
		$readonly,
		$type,
		$value;

	public function generateHeader()
	{
		$js = "/**\n * ".$this->name."\n * ".$this->description;
		if ($this->readonly)
		{
			$js .= "\n * READONLY!";
		}
		if ($this->type)
		{
			$js .= "\n * @var ".$this->type;
		}
		$js .= "\n */\n";
		return $js;
	}

	public function generateBody()
	{
		$js = ($this->readonly?'const':'var')." ".$this->name." = ".($this->value?$this->value:'null').";\n";
		return $js."\n\n";
	}

	public function getJS()
	{
		return $this->generateHeader().$this->generateBody();
	}
}


/**
 * Represents a XUL binding property.
 */
class uixul_BindingProperty extends uixul_BindingField
{
	public
		$getterOrSetter, $body;

	public function generateHeader()
	{
		$js = "/**\n * ".$this->name."\n * ".$this->description;
		if ($this->readonly)
		{
			$js .= "\n * READONLY!";
		}
		if ($this->type)
		{
			$js .= "\n * @var ".$this->type;
		}
		$js .= "\n */\n";
		return $js;
	}

	public function generateBody()
	{
		$js = "function _property_".$this->getterOrSetter.'_'.$this->name."(".($this->getterOrSetter=='setter'?'val':'').")";
		$js .= "\n{\n".$this->body."\n}";
		ob_start();
		js_beautify($js);
		$js = ob_get_clean();
		$js = preg_replace('/{\s*K\s*::\s*([A-Z_]+)\s*}/m', '{K::$1}', $js);
		return $js."\n\n\n";
	}
}