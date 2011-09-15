<?php
class commands_CompileEditorsConfig extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	function getAlias()
	{
		return "cec";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile document editors configuration";
	}


	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== compile document editors configuration ==");
		
		$this->loadFramework();

		uixul_DocumentEditorService::getInstance()->compileEditorsConfig();
		
		CacheService::getInstance()->boShouldBeReloaded();
		
		$this->quitOk("Document editor configuration compiled");
	}
}