<?php
class uixul_lib_cRichTextBlockToolbarTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$ls = LocaleService::getInstance();
		$addons = array();
		foreach (uixul_RichtextConfigService::getInstance()->getConfigurationArray() as $styleInfos)
		{
			$tag = $styleInfos['tag'] . '.' . $styleInfos['class'];
			$label = isset($styleInfos['labeli18n']) ? $ls->trans($styleInfos['labeli18n'], array('ucf')) : $styleInfos['label'];
			$command = ($styleInfos['block']) ? 'formatblock' : 'surround';
			$addons[] = sprintf('<menuitem anonid="%s" type="checkbox" autocheck="false" label="%s" oncommand="applyStyle(\'%s\', \'%s\')"/>', $tag, LocaleService::getInstance()->trans($label), $command, $tag);
		}	
	
		if (count($addons))		{
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