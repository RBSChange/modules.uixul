<?php
class uixul_MoreHelpAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
    	if ($request->hasParameter("message"))
    	{
    		$request->setAttribute("message", f_Locale::translate("&" . $request->getParameter("message") . ";"));
    		$request->setAttribute("icon", MediaHelper::getIcon('help2', MediaHelper::NORMAL));
    	}
    	else
    	{
    	    $request->setAttribute("message", f_Locale::translate("&modules.uixul.backoffice.NoMoreHelp;"));
    	    $request->setAttribute("icon", MediaHelper::getIcon('unknown', MediaHelper::NORMAL));
    	}
    	return View::SUCCESS;
    }
}