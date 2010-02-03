<?php
class uixul_MoreHelpSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

    	$this->setTemplateName('Uixul-More-Help', K::HTML);
        $this->setAttribute(
            'styles',
            $this->getStyleService()
                ->registerStyle('modules.uixul.dialog')
                ->execute(K::HTML)
        );
        $this->setAttribute('icon', $request->getAttribute("icon"));
        $this->setAttribute('message', $request->getAttribute("message"));

        $rq->endI18nWork();
    }
}