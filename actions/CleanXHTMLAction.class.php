<?php
/**
 * uixul_CleanXHTMLAction
 * @package modules.uixul.actions
 */
class uixul_CleanXHTMLAction extends f_action_BaseAction
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
		$response = new DOMDocument('1.0', 'UTF-8');
		$response->loadXML('<response><action>CleanXHTML</action><module>uixul</module><status>OK</status></response>');
		$cdata = $response->createCDATASection($cleanxhtml);
		$content = $response->createElement('content');
		$content->appendChild($cdata);
		$response->documentElement->appendChild($content);
		echo $response->saveXML();
		return View::NONE;
	}
}