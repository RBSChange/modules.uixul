<?php
class uixul_GetUICSSAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
		header('Content-type: text/css');
		$stylename = $request->getParameter('stylename');
		$ss = website_StyleService::getInstance();
		$skinId = $request->getParameter('skinId');
		$skin =  ($skinId) ? DocumentHelper::getDocumentInstance($skinId) : null;
		if ($request->hasParameter('uilang'))
		{
			$lang = $request->getParameter('uilang');
			$rc = RequestContext::getInstance();
			if (in_array($lang, $rc->getUISupportedLanguages()))
			{
				$rc->setUILang($lang);
			}
		}
		
		echo $ss->getCSS($stylename, $ss->getFullEngineName('xul'), $skin);
		return View::NONE;
	}
}