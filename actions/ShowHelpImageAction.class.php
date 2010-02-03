<?php
class uixul_ShowHelpImageAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    	$moduleName = $request->getParameter('modname');
		$imageName = $request->getParameter('cmpref');
        	$imageLocation = Resolver::getInstance('file')
        	    ->setPackageName('modules_'.$moduleName)
        	    ->setDirectory('doc/help')
        	    ->getPath($imageName);  
		if (!$imageLocation)
		{
        		$imageLocation = Resolver::getInstance('file')
        		    ->setPackageName('modules_'.$moduleName)
        		    ->setDirectory('doc/help')
        		    ->getPath(ucfirst($imageName));  
		}
        	if ($imageLocation)
        	{
        	    MediaHelper::outputExternalFile($imageLocation, array('max-width' => '400'), true);
        	}
		return View::NONE;
	}
}
