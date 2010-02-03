<?php
class uixul_DocAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$binding = $request->getParameter('binding');
		if (!strcasecmp($binding, 'xsl'))
		{
			header("Content-type: text/xml");
			header("Cache-control: no-cache");
			readfile(dirname(__FILE__).'/../doc/binding-doc.xsl');
			exit;
		}
		else if (!strcasecmp($binding, 'css'))
		{
			header("Content-type: text/css");
			header("Cache-control: no-cache");
			readfile(dirname(__FILE__).'/../doc/binding-doc.css');
			exit;
		}
		if (empty($binding))
		{
			return 'Toc';
		}

		$bindingFile = uixul_lib_BindingObject::getFile($binding);
		$bindingContent = trim(file_get_contents($bindingFile));
		
		$beginPos = strpos($bindingContent, "<bindings");
		if ($beginPos === false)
		{
			die("Bad binding file.");
		}
		
		$xmlHeader =
			'<?xml version="1.0"?>' . "\n" .
			'<?xml-stylesheet href="/xul_controller.php?module=uixul&action=Doc&binding=xsl" type="text/xsl"?>' . "\n\n";
		
		$bindingContent = substr_replace($bindingContent, $xmlHeader, 0, $beginPos);
		$bindingContent = str_replace('xmlns="http://www.mozilla.org/xbl"', '', $bindingContent);
		header("Content-type: text/xml");
		die ($bindingContent);
	}
}