<?php
/**
 * @date Thu Jan 25 16:05:19 CET 2007
 * @author INTbonjF
 */
class uixul_AboutAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{   
        $request->setParameter('frameworkVersion', FRAMEWORK_VERSION);
        $request->setParameter('modules', ModuleService::getInstance()->getModulesObj());
		return change_View::SUCCESS;
	}

	public function isSecure()
	{
		return true;
	}
}