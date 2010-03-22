<?php
class uixul_GetAdminJavascriptsAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
		header('Content-type: application/x-javascript');		
	    $rq = RequestContext::getInstance();
	    $rq->setUILangFromParameter($request->getParameter('uilang'));
        $rq->beginI18nWork($rq->getUILang());
        
		$jss = JsService::getInstance();
		$jss->registerScript($request->getParameter('name', 'modules.uixul.lib.default'));
		$jss->generateXulLibrary();
		
		$rq->endI18nWork();		
		return View::NONE;
	}
}