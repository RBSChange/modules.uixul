<?php
class uixul_EditLocaleAction extends f_action_BaseAction
{

	/**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        $request->setAttribute('moduleName', $this->getModuleName($request));

		return View::INPUT;
	}
}