<?php
/**
 * CleanXHTMLJSONAction
 * @package modules.uixul.actions
 */
class uixul_CleanXHTMLJSONAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$xhtml = $request->getParameter('xhtml');
		Framework::info($xhtml);
		$cleanxhtml = website_XHTMLCleanerHelper::clean($xhtml);
		Framework::info($cleanxhtml);
		return $this->sendJSON(array($cleanxhtml));
	}
	
	/**
	 * @see f_action_BaseAction::isSecure()
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}

}