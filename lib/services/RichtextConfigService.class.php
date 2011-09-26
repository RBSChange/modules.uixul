<?php
class uixul_RichtextConfigService extends BaseService
{
	/**
	 * @deprecated use the value directly.
	 */
	const TAG_ATTRIBUTE_NAME = 'tag';
	
	/**
	 * @deprecated use the value directly.
	 */
	const LABEL_ATTRIBUTE_NAME = 'label';
	
	/**
	 * @deprecated use the value directly.
	 */
	const ATTR_ATTRIBUTE_NAME = 'attributes';

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
				continue;
			}
			$styles = $domDoc->getElementsByTagName('style');
			foreach ($styles as $style)
			{
				$props = array();
				
				/* @var $style DOMElement */
				$tagName = $style->getAttribute('tag');
				if (!$tagName)
				{
					Framework::error(__METHOD__ . ": style has no valid 'tag' attribute");
					continue;
				}
				$props['tag'] = $tagName;
				
				$className = $style->getAttribute('class');
				if (!$className)
				{
					Framework::error(__METHOD__ . ": style has no valid 'class' attribute");
					continue;
				}
				$props['class'] = $className;

				$labelI18n = $style->getAttribute('labeli18n');
				$label = $style->getAttribute('label');
				if ($labelI18n)
				{
					$props['labeli18n'] = $labelI18n;
				}
				elseif ($label)
				{
					$props['label'] = $label;					
				}
				else
				{
					Framework::error(__METHOD__ . ": style has no valid 'labeli18n' nor 'label' attribute");
					continue;
				}
				
				$block = $style->getAttribute('class');
				$props['block'] = ($block != 'false');
				
				$config[$tagName.'.'.$className] = $props;
			}
		}
		return $config;
	}

	/**
	 * @return array
	 */
	protected function getConfigurationFilePaths()
	{
		$result = array();
		$moduleList = array('website', 'richtext'); // website module contains static definitions and richtext contains dynamic ones.
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
}