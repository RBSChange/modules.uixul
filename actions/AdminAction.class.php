<?php
/**
 * @date Thu Jan 25 16:05:19 CET 2007
 * @author INTbonjF
 */
class uixul_AdminAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		return View::SUCCESS;
	}

	public function isSecure()
	{
		return true;
	}
}