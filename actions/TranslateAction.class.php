<?php
class uixul_TranslateAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		$formatters = $request->getParameter('formatters');
		if (is_string($formatters))
		{
			$formatters = array($formatters);
		}
		echo LocaleService::getInstance()->transBO($request->getParameter('key'), is_array($formatters) ? $formatters : array());
		return View::NONE;
	}
}