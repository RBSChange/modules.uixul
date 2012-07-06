<?php
/**
 * Provides some basic methods to handle our XUL bindings.
 */
class uixul_lib_BindingObject
{
	/**
	 * Gets the URL of the binding from its short name. This calls a server
	 * action that will parse the binding, cache and return it.
	 *
	 * @param string $bindingShortName The short name of the binding (ie. "core.wController")
	 *
	 * @return string The URL of the binding.
	 */
	public static function getUrl($bindingShortName, $replaceXmlEntities = false)
	{
		if (strpos($bindingShortName, '://') !== false) {return $bindingShortName;}
		$infos = explode('#', $bindingShortName);				
		$link = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')
			->setQueryParameter('uilang', RequestContext::getInstance()->getUILang())
			->setQueryParameter('binding', $infos[0]);
		if (isset($infos[1]))
		{
			$link->setFragment($infos[1]);
		}
		if ($replaceXmlEntities)
		{
			$link->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
		}
		return $link->getUrl();
	}


	/**
	 * Gets the binding file from its short name.
	 *
	 * @param string $bindingShortName The short name of the binding (ie. "core.wController")
	 *
	 * @return string The absolute path to the binding file.
	 */
	public static function getFile($bindingShortName)
	{
		$info = explode('.', $bindingShortName);
		if ($info[0] === 'modules' || $info[0] === 'module' && count($info) >= 3)
		{
			array_shift($info);						// remove 'module' (or 'modules')
			$moduleName = array_shift($info);		// remove module name
		}
		else
		{
			$moduleName = 'uixul';
		}		
		$fileName = join(DIRECTORY_SEPARATOR, $info) . '.xml';
		$bindingFile = change_FileResolver::getNewInstance()->getPath('modules', $moduleName, 'lib', 'bindings', $fileName);
		return $bindingFile;
	}
	
	/**
	 * @param string $bindingShortName
	 * @return TemplateObject
	 * @throws Exception
	 */
	public static function getTemplateObject($bindingShortName)
	{
		$info = explode('.', $bindingShortName);
		if ($info[0] === 'modules' || $info[0] === 'module' && count($info) >= 3)
		{
			array_shift($info);						// remove 'module' (or 'modules')
			$moduleName = array_shift($info);		// remove module name
		}
		else
		{
			$moduleName = 'uixul';
		}
		$fileName = join(DIRECTORY_SEPARATOR, $info);
		$templateObject = change_TemplateLoader::getNewInstance()->setExtension('xml')->load('modules', $moduleName, 'lib', 'bindings', $fileName);
		if ($templateObject === null)
		{
			throw new Exception('Template not found');
		}
		return $templateObject;
	}
}