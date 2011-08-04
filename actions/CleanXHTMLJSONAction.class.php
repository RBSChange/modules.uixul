<?php
/**
 * CleanXHTMLJSONAction
 * @package modules.uixul.actions
 */
class uixul_CleanXHTMLJSONAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$xhtml = $request->getParameter('xhtml');
		$cleanxhtml = website_XHTMLCleanerHelper::clean($xhtml);
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