<?php
class uixul_TranslateAction extends f_action_BaseAction
{
	
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		echo f_Locale::translateUI('&' . $request->getParameter('key') . ';');
		return View::NONE;
	}
}