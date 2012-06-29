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
			$package = 'modules_' . $moduleName;
		}
		else
		{
			$package = 'modules_uixul';
		}		
		$fileName = join(DIRECTORY_SEPARATOR, $info) . '.xml';
		$bindingFile = FileResolver::getInstance()->setPackageName($package)
						->setDirectory('lib/bindings')->getPath($fileName);
		return $bindingFile;
	}
	
	/**
	 * @param string $bindingShortName
	 * @return TemplateObject
	 * @throws TemplateNotFoundException
	 */
	public static function getTemplateObject($bindingShortName)
	{
		$info = explode('.', $bindingShortName);
		if ($info[0] === 'modules' || $info[0] === 'module' && count($info) >= 3)
		{
			array_shift($info);						// remove 'module' (or 'modules')
			$moduleName = array_shift($info);		// remove module name
			$package = 'modules_' . $moduleName;
		}
		else
		{
			$package = 'modules_uixul';
		}
		$fileName = join(DIRECTORY_SEPARATOR, $info);
		return TemplateLoader::getInstance()->setMimeContentType('xml')->setPackageName($package)
				->setDirectory('lib/bindings')->load($fileName);
	}
}