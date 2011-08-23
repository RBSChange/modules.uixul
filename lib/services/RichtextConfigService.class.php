<?php
class uixul_RichtextConfigService extends BaseService
{
	const TAG_ATTRIBUTE_NAME = "tag";
	const LABEL_ATTRIBUTE_NAME = "label";
	const ATTR_ATTRIBUTE_NAME = "attributes";
	
	/**
	 * @var uixul_RichtextConfigService
	 */
	private static $instance;
	
	/**
	 * @return uixul_RichtextConfigService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @return Array
	 */
	public function getConfigurationArray()
	{
		$config = array();
		$domDoc = new DOMDocument();
		foreach ($this->getConfigurationFilePaths() as $file)
		{
			if (!$domDoc->load($file))
			{
				Framework::error(__METHOD__ . ": $file is not a valid XML");
			}
			$styles = $domDoc->getElementsByTagName('style');
			foreach ($styles as $style)
			{
				if (!$style->hasAttribute(self::TAG_ATTRIBUTE_NAME))
				{
					Framework::error(__METHOD__ . ": style has no " . self::TAG_ATTRIBUTE_NAME . " attribute");
					continue;
				}
				
				$tagName = $style->getAttribute(self::TAG_ATTRIBUTE_NAME);
				$label = $style->getAttribute(self::LABEL_ATTRIBUTE_NAME);
				$props = array(self::TAG_ATTRIBUTE_NAME => $tagName, self::LABEL_ATTRIBUTE_NAME => $label);
				foreach ($style->attributes as $attrName => $attrNode)
				{
					if ($attrName == self::TAG_ATTRIBUTE_NAME || $attrName == self::LABEL_ATTRIBUTE_NAME || $attrName == self::ATTR_ATTRIBUTE_NAME)
										{
						continue;
					}
					$props[$attrName] = $attrNode->nodeValue;
				}
				$props[self::ATTR_ATTRIBUTE_NAME] = $this->buildAttributes($style, $config);
				$config[] = $props;
			}
		}
		return $config;
	}
	
	/**
	 * @return Array
	 */
	protected function getConfigurationFilePaths()
	{
		$result = array();
		$moduleList = array('generic', 'website');
		foreach ($moduleList as $module)
		{
			$path = $this->resolveConfigurationFilesForModule($module);
			if ($path !== null)
			{
				$result[] = $path;
			}
		}
		return $result;
	}

	/**
	 * @param String $moduleName
	 * @return String
	 */
	private function resolveConfigurationFilesForModule($moduleName)
	{
		return FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('config')->getPath('richtext.xml');
	}
	
	/**
	 * @param DOMElement $styleNode
	 * @param array $config
	 */
	private function buildAttributes($styleNode)
	{
		$attrs = array();
		$attributes = $styleNode->getElementsByTagName('attribute');
		foreach ($attributes as $attribute)
		{
			if (!$attribute->hasAttribute("name"))
			{
				Framework::error(__METHOD__ . ": attribute has no name");
				continue;
			}
			
			if (!$attribute->hasAttribute("value"))
			{
				Framework::error(__METHOD__ . ": attribute has no value");
				continue;
			}
			$attrs[$attribute->getAttribute("name")] = $attribute->getAttribute("value");
		}
		return $attrs;
	}
}