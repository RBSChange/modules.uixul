<?php
class uixul_GetAdminJavascriptsAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
		header('Content-type: application/x-javascript');		
	    $rq = RequestContext::getInstance();
	    $rq->setUILangFromParameter($request->getParameter('uilang'));
        $rq->beginI18nWork($rq->getUILang());
        
		$jss = website_JsService::getInstance();
		$jss->registerScript($request->getParameter('name', 'modules.uixul.lib.default'));
		$jss->generateXulLibrary();
		
		$rq->endI18nWork();		
		return change_View::NONE;
	}
}