<?php
class commands_GenerateDocumentEditor extends commands_AbstractChangeCommand
{
	private static $options = array("perspective", "resume", "properties", "publication", "localization", "history", "create", "panels");
	 
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName[/documentName]> [element = create|history|localization|panels|perspective|properties|publication|resume]";
	}

	function getAlias()
	{
		return "gde";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "generate document editor";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		$paramsCount = count($params);
		if ($paramsCount == 1)
		{
			return true;
		}
		if ($paramsCount == 2)
		{
			return in_array($params[1], self::$options);
		}
		return false;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options)
	{
		if ($completeParamCount == 0)
		{
			$this->loadFramework();
			$modelNames = array();
			foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules() as $module => $names)
			{
				$modelNames[] = $module;
				foreach ($names as $name)
				{
					$modelNames[] = substr($name, 8);
				}
			}
			return $modelNames;
		}
		if ($completeParamCount == 1)
		{
			return self::$options;
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Generate document editor ==");

		$this->loadFramework();
		$panels = null;
		list ($moduleName, $documentName) = explode("/", $params[0]);

		if (!defined('MOD_' . strtoupper($moduleName)  . '_ENABLED'))
		{
			return $this->quitError("Invalid module name : " . $moduleName);
		}

		if (isset($params[1]))
		{
			$panels = explode(",", $params[1]);
		}
		else
		{
			$panels = array("perspective");
		}

		if ($documentName !== null)
		{
			if (!f_persistentdocument_PersistentDocumentModel::exists("modules_$moduleName/$documentName"))
			{
				return $this->quitError("Invalid document name : " . $moduleName."/$documentName");
			}
		}

		if (($perspectiveIndex = array_search("perspective", $panels)) !== false)
		{
			$this->log("Processing $moduleName perspective...");
			$moduleBindingService = uixul_ModuleBindingService::getInstance();
			if ($moduleBindingService->hasConfigFile($moduleName))
			{
				$this->warnMessage("  Perspective already exists");
			}
			else
			{
				$this->message("  Convert old files...");
				$doc = uixul_ModuleBindingService::getInstance()->getConvertedConfig($moduleName);
				$path = f_util_FileUtils::buildWebeditPath('modules', $moduleName, 'config', 'perspective.xml');
				$this->message("  Perspective created  : " . $path);
				$doc->save($path);
			}
			unset($panels[$perspectiveIndex]);
		}

		if ($documentName !== null)
		{
			$this->message("Processing $moduleName/$documentName ".join(", ", $panels)."...");
			uixul_DocumentEditorService::getInstance()->buildDefaultDocumentEditors($moduleName, $documentName, $panels);
		}

		$this->quitOk("Document editor generated");
	}
}