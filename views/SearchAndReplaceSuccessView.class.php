<?php
class uixul_SearchAndReplaceSuccessView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$rq = RequestContext::getInstance();

		$rq->beginI18nWork($rq->getUILang());

		// Set our template
		$this->setTemplateName('Uixul-SearchAndReplace-Success', K::XUL);

		$modules = array('generic', 'uixul', 'website');
		$ss = website_StyleService::getInstance();
		foreach ($modules as $module)
		{
			$ss->registerStyle('modules.'.$module.'.backoffice')->registerStyle('modules.'.$module.'.bindings');
		}

		$this->setAttribute('cssInclusion', $ss->execute(K::XUL));
		$jss = website_JsService::getInstance();
		$jss->registerScript('modules.uixul.lib.wCore');
		$this->setAttribute('scriptInclusion', $jss->executeInline(K::XUL));
		$rq->endI18nWork();
	}
}