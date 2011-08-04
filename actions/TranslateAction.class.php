<?php
class uixul_TranslateAction extends change_Action
{
	
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	protected function _execute($context, $request)
	{
		echo f_Locale::translateUI('&' . $request->getParameter('key') . ';');
		return change_View::NONE;
	}
}