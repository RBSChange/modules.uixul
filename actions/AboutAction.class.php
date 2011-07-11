<?php
/**
 * @date Thu Jan 25 16:05:19 CET 2007
 * @author INTbonjF
 */
class uixul_AboutAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{   
        $request->setParameter('frameworkVersion', FRAMEWORK_VERSION);
        $request->setParameter('frameworkHotfix', FRAMEWORK_HOTFIX);
        $request->setParameter('modules', ModuleService::getInstance()->getModulesObj());
		return View::SUCCESS;
	}

	public function isSecure()
	{
		return true;
	}
}