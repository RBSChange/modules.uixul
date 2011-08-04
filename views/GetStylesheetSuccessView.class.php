<?php
class uixul_GetStylesheetSuccessView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
	    $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Ui-Stylesheet', 'css');

		if (!headers_sent())
		{
		      header('Content-type: text/css');
		}

		$this->setAttribute('contents', $request->getAttribute('contents'));

		$rq->endI18nWork();
	}
	
	protected function sendHttpHeaders()
	{
	}
}
