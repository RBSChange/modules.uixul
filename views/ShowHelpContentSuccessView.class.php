<?php
class uixul_ShowHelpContentSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Show-Help-Content', K::HTML);

		$this->setAttribute('helpContent', $request->getAttribute('helpContent'));

        $this->setAttribute(
            'cssInclusion',
            $this->getStyleService()
                ->registerStyle('modules.uixul.help')
                ->execute(K::HTML)
        );

        $rq->endI18nWork();
	}
}