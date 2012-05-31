<?php
class uixul_TranslateAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	protected function _execute($context, $request)
	{
		$formatters = $request->getParameter('formatters');
		if (is_string($formatters))
		{
			$formatters = array($formatters);
		}
		echo LocaleService::getInstance()->trans($request->getParameter('key'), is_array($formatters) ? $formatters : array());
		return change_View::NONE;
	}
}