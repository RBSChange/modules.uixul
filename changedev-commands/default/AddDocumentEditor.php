<?php
class commands_AddDocumentEditor extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName> <name> [parents]
- If you do not not provide [parents] parameter, its value will be 'generic/rootfolder,generic/folder' or 'website/topic', depending on the nature of the module ('folder' or 'topic' based)";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "generates a backoffice interface for a document.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) >= 2;
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
			return $components;
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add document editor ==");

		$moduleName = $params[0];
		$documentName = $params[1];
		
		$this->loadFramework();
		
		if (isset($params[2]))
		{
			$parents = explode(',', $params[2]);
		}
		else
		{
			$module  = ModuleService::getInstance()->getModule($moduleName);
			
			if ($module->isTopicBased())
			{
				$parents = array("website/topic");
			}
			else
			{
				$parents = array("generic/rootfolder", "generic/folder");
			}
		}

		// Get a document Generator
		$documentGenerator = new builder_DocumentGenerator($moduleName, $documentName);
		$documentGenerator->setAuthor($this->getAuthor());

		$documentGenerator->generateBoLocaleFile();
		$documentGenerator->addStyleInBackofficeFile();

		if ($documentName !== "preferences")
		{
			$documentGenerator->addBackofficeAction($parents);
		}

		$this->changecmd("compile-locales", array($moduleName));
		$this->changecmd("compile-editors-config");
		$this->changecmd("clear-webapp-cache");
		
		$this->quitOk("Document $documentName's backoffice interface generated.");
	}
}