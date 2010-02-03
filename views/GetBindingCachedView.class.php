<?php
class uixul_GetBindingCachedView extends f_view_BaseView
{
	protected function sendHttpHeaders()
	{
	}
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Raw', K::XML, 'uixul');
		$cachedFile = $request->getAttribute('cachedFile');
		$this->setAttribute('contents', f_util_FileUtils::read($cachedFile));

		$rq->endI18nWork();
	}
}