<?php
class commands_CompileDocumentEditor extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<module> [option = config | editor]";
	}

	function getAlias()
	{
		return "cde";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile document editor";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1 || count($params) == 2;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return array_diff($components, $params);
		}
		if ($completeParamCount == 1)
		{
			return array("editor", "config");
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile document editor ==");
		
		$this->loadFramework();

		$module = $params[0];
		if (isset($params[1]))
		{
			$option = $params[1];
		}
		
		if (!ModuleService::getInstance()->moduleExists($module))
		{
			return $this->quitError("Invalid module name : " . $module);
		}

		if (!isset($params[1]))
		{
			uixul_DocumentEditorService::getInstance()->compileDocumentEditors($module);
		}
		else if ($option === 'editor')
		{
			uixul_DocumentEditorService::getInstance()->buildDefaultDocumentEditors($module);
		}
		else if ($option === 'config')
		{
			if (uixul_ModuleBindingService::getInstance()->hasConfigFile($module))
			{
				return $this->quitWarn("Config file already exist for module: " . $module);
			}

			$doc = uixul_ModuleBindingService::getInstance()->getConvertedConfig($module);
			$path = f_util_FileUtils::buildWebeditPath('modules', $module, 'config', 'perspective.xml');

			$this->okMessage("Config file created : " . $path);
			$doc->save($path);
		}

		$this->quitOk("Document editor compiled");
	}
}