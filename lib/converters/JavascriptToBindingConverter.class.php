<?php
include_once 'beautifier.php';

/**
 * Converts a JavaScript file into a XUL binding file (<implementation />).
 */
class uixul_JavaScriptToBindingConverter implements uixul_Converter
{
	/**
	 * Converts the JavaScript file $jsFile to a XUL binding contents and
	 * returns it as a string.
	 *
	 * @param string $jsFile
	 * @return string
	 */
	public function convert($jsFile)
	{
		$js = file_get_contents($jsFile);
		$xml = '';

		// Handle inclusions.
		$matches = null;
		if (preg_match_all("/include\s+['\"]([\w\d_\-\.]+)['\"]/mi", $js, $matches, PREG_SET_ORDER))
		{
			foreach($matches as $match)
		    {
		    	$js = str_replace($match[0], file_get_contents(dirname($jsFile).DIRECTORY_SEPARATOR.$match[1]), $js);
		    }
		}

		// Handle translations.
		if (preg_match_all('/&(amp;)?modules\.([^;]+);/m', $js, $matches, PREG_SET_ORDER))
		{
		    foreach($matches as $match)
		    {
				$js = str_replace($match[0], f_Locale::translateUI(str_replace('&amp;', '&', $match[0])), $js);
		    }
		}

		// convert fields
		if (preg_match_all('/^(var|const)\s+([a-zA-Z0-9_]+)(\s*=\s*([^;]+))?;$/m', $js, $matches))
		{
			$decls = $matches[0];
			$varConst = $matches[1];
			$varNames = $matches[2];
			$values   = $matches[4];

			foreach ($decls as $index => $decl)
			{
				$field = new uixul_JsField();
				$field->name = $varNames[$index];
				$field->readonly = $varConst[$index] == 'const';
				$field->value = $values[$index];
				$xml .= $field->getXML();
			}
		}

		// convert properties
		$xulPropertiesFound = array();
		if (preg_match_all('/^function\s+_property_(getter|setter)_([a-zA-Z0-9_]+)\s*\(/m', $js, $matches))
		{
			$methodsDecl = $matches[0];
			$methods = $matches[2];
			$getterOrSetter = $matches[1];
			$bodys   = array();

			$propertyObjects = array();

			// find methods body
			foreach ($methodsDecl as $index => $decl)
			{
				$type = $getterOrSetter[$index];
				$name = $methods[$index];

				$p = strpos($js, $decl);
				$ob = strpos($js, '{', $p);
				$openBracketsCount = 1;

				$cb = $ob + 1;
				while ($openBracketsCount > 0 && $cb < strlen($js))
				{
					if ($js{$cb} == '{') $openBracketsCount++;
					if ($js{$cb} == '}') $openBracketsCount--;
					$cb++;
				}
				$cb--;
				$body = trim(substr($js, $ob+1, $cb-$ob-1), "\n");

				if (!isset($xulPropertiesFound[$name]))
				{
					$xulPropertiesFound[$name] = new uixul_JsProperty();
					$xulPropertiesFound[$name]->name = $name;
				}
				$method = $xulPropertiesFound[$name];
				if ($type=="getter")
				{
					$method->getter = $body;
				}
				if ($type=="setter")
				{
					$method->setter = $body;
				}
			}

			foreach ($xulPropertiesFound as $property)
			{
				$xml .= $property->getXML();
			}

			foreach ($methodsDecl as $decl)
			{
				$js = str_replace($decl, '', $js);
			}
		}

		// convert methods
		if (preg_match_all('/^function\s+([a-zA-Z0-9_]+)\s*\(([^\)]*)\)/m', $js, $matches))
		{
			$methodsDecl = $matches[0];
			$methods = $matches[1];
			$params  = $matches[2];
			$bodys   = array();

			$methodObjects = array();

			// find methods body
			foreach ($methodsDecl as $index => $decl)
			{
				$p = strpos($js, $decl);
				$ob = strpos($js, '{', $p);
				$openBracketsCount = 1;

				$cb = $ob + 1;
				while ($openBracketsCount > 0 && $cb < strlen($js))
				{
					if ($js{$cb} == '{') $openBracketsCount++;
					if ($js{$cb} == '}') $openBracketsCount--;
					$cb++;
				}
				$cb--;
				$bodys[$index] = trim(substr($js, $ob+1, $cb-$ob-1), "\n");

				$method = new uixul_JsMethod();
				$method->name = $methods[$index];
				$params[$index] = trim($params[$index]);
				if (!empty($params[$index]))
				{
					$method->parameters = explode(',', $params[$index]);
				}
				else
				{
					$method->parameters = array();
				}
				$method->body = $bodys[$index];
				$xml .= $method->getXML();
			}
		}

		return $xml;
	}


	/**
	 * Indents the content provided in $code of $nb tabulations.
	 *
	 * @param string $code The content to indent.
	 * @param integer $nb Number of tabulations
	 * @return string The indented content.
	 */
	public static function indent($code, $nb = 1)
	{
		$lines = explode("\n", $code);
		foreach ($lines as &$line)
		{
			$line = str_repeat("\t", $nb) . $line;
		}
		return join("\n", $lines);
	}
}


/**
 * Represents a JavaScript function (method).
 */
class uixul_JsMethod
{
	public
		$name,
		$description,
		$parameters,
		$body,
		$returnType;

	public function generateHeader()
	{
		return "<!--\n".$this->name."\n-->\n";
	}

	public function generateBody()
	{
		$xml = "<method name=\"".$this->name."\">";
		$nbp = count($this->parameters);
		for ($i=0 ; $i < $nbp ; $i++)
		{
			$xml .= "\n\t<parameter name=\"".trim($this->parameters[$i])."\" />";
		}
		$xml .= "\n\t<body><![CDATA[\n";
		ob_start();
		js_beautify($this->body);
		$xml .= preg_replace('/{\s*K\s*::\s*([A-Z_]+)\s*}/m', '{K::$1}', uixul_JavaScriptToBindingConverter::indent(ob_get_clean(), 2));
		$xml .= "\n\t]]></body>\n</method>\n\n";
		return $xml;
	}

	public function getXML()
	{
		return $this->generateHeader().$this->generateBody();
	}
}


/**
 * Represents a JavaScript field.
 */
class uixul_JsField
{
	public
		$name,
		$description,
		$type,
		$value,
		$readonly;

	public function generateHeader()
	{
		return "<!--\n".$this->name."\n-->\n";
	}

	public function generateBody()
	{
		$xml = "<field name=\"".$this->name."\"";
		if ($this->readonly)
		{
			$xml .= " readonly=\"true\"";
		}
		if ($this->value)
		{
			$xml .= ">" . $this->value . "</field>";
		}
		else
		{
			$xml .= "/>";
		}
		return $xml."\n\n";
	}

	public function getXML()
	{
		return $this->generateHeader().$this->generateBody();
	}
}


/**
 * Represents a JavaScript property.
 */
class uixul_JsProperty
{
	public
		$name,
		$type,
		$getter,
		$setter,
		$readonly,
		$body;

	public function generateHeader()
	{
		return "<!--\n".$this->name."\n-->\n";
	}

	public function generateBody()
	{
		$xml = "<property name=\"".$this->name."\"";
		if ($this->readonly)
		{
			$xml .= " readonly=\"true\"";
		}
		$xml .= ">";

		if ($this->getter)
		{
			$xml .= "\n\t<getter><![CDATA[\n";
			ob_start();
			js_beautify($this->getter);
			$xml .= preg_replace('/{\s*K\s*::\s*([A-Z_]+)\s*}/m', '{K::$1}', uixul_JavaScriptToBindingConverter::indent(ob_get_clean(), 2));
			$xml .= "\n\t]]></getter>";
		}
		if ($this->setter)
		{
			$xml .= "\n\t<setter><![CDATA[\n";
			ob_start();
			js_beautify($this->setter);
			$xml .= preg_replace('/{\s*K\s*::\s*([A-Z_]+)\s*}/m', '{K::$1}', uixul_JavaScriptToBindingConverter::indent(ob_get_clean(), 2));
			$xml .= "\n\t]]></setter>";
		}
		$xml .= "\n</property>";
		return $xml."\n\n";
	}

	public function getXML()
	{
		return $this->generateHeader().$this->generateBody();
	}
}
