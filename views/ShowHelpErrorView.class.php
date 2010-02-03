<?php
class uixul_ShowHelpErrorView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Show-Help-Error', K::XUL);

		$this->setAttribute(
            'cssInclusion',
            $this->getStyleService()
    	    	  ->registerStyle('modules.uixul.backoffice')
    	    	  ->execute(K::XUL)
        );

		$this->getJsService()->registerScript('modules.uixul.lib.default');
        
		$this->setAttribute(
            'scriptInclusion',
            $this->getJsService()->executeInline(K::XUL)
		);

		$rq->endI18nWork();
	}
}
