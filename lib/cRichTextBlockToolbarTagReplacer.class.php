<?php
class uixul_lib_cRichTextBlockToolbarTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$addons = array();
		foreach (uixul_RichtextConfigService::getInstance()->getConfigurationArray() as $configDeclaration)
		{
			// Skip declarations without label.
			if ($configDeclaration[uixul_RichtextConfigService::LABEL_ATTRIBUTE_NAME] == '')
			{
				continue;
			}
			
			$tag = $configDeclaration[uixul_RichtextConfigService::TAG_ATTRIBUTE_NAME];
			if (isset($configDeclaration[uixul_RichtextConfigService::ATTR_ATTRIBUTE_NAME]))
			{
				if (isset($configDeclaration[uixul_RichtextConfigService::ATTR_ATTRIBUTE_NAME]['class']))
				{
					$tag .= "." . $configDeclaration[uixul_RichtextConfigService::ATTR_ATTRIBUTE_NAME]['class'];
				}
			}
			$label = $configDeclaration[uixul_RichtextConfigService::LABEL_ATTRIBUTE_NAME];
			$module = isset($configDeclaration['module']) ? $configDeclaration['module'] : '';
			$document = isset($configDeclaration['document']) ? $configDeclaration['module'] : '';
			if (isset($configDeclaration['module']))
			{
				$module = ' module="' . $configDeclaration['module'] . '"';
				if (isset($configDeclaration['document']))
				{
					$document = ' document="' . $configDeclaration['module'] . '"';
				}
				else
				{
					$document = ' document="all"';
				}
			}
			else
			{
				$module = '';
				$document = '';
			}
			if (isset($configDeclaration['block']) && ($configDeclaration['block'] == "false"))
			{
				$command = 'surround';
			}
			else
			{
				$command = 'formatblock';
			}
			
			$addons[] = sprintf('<menuitem anonid="%s"%s%s type="checkbox" autocheck="false" label="%s" oncommand="applyStyle(\'%s\', \'%s\')"/>', $tag, $module, $document, f_Locale::translateUI($label), $command, $tag);
			
		}	
	
		if (count($addons))
		{
			array_unshift($addons, '<menuseparator />');
		}
		$addonStyles = array('ADDON_STYLES_MENU' => implode(PHP_EOL, $addons));
		
		foreach ($addonStyles as $key => $value)
		{
			$this->setReplacement($key, $value);
		}
		
		// Handle buttons disabling.
		$disableArray = Framework::getConfiguration('modules/uixul/disableRichtextTtoolbarButtons', false);
		$disableCode = '';
		if (is_array($disableArray))
		{
			foreach ($disableArray as $name => $value)
			{
				if ($value == 'true')
				{
					$disableCode .= "this.getElementById('$name').setAttribute('collapsed', 'true');\n";
				}
			}
		}
		$this->setReplacement('DISABLE_BUTTONS', $disableCode);
	}	
}