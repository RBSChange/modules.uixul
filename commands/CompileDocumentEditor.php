<?php
class commands_CompileDocumentEditor extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}

	function getAlias()
	{
		return "";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "";
	}
	
	/**
	 * @see c_ChangescriptCommand::isHidden()
	 */
	public function isHidden()
	{
		return true;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1 || count($params) == 2;
	}

	/**
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
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
	 * @param string[] $params
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
			return $this->quitError('Invalid option: config');
		}

		return $this->quitOk("Document editor compiled");
	}
}