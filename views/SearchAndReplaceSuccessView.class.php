<?php
class uixul_SearchAndReplaceSuccessView extends f_view_BaseView
{

    /**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

        // Set our template
        $this->setTemplateName('Uixul-SearchAndReplace-Success', K::XUL);

        $modules = array('generic', 'uixul', 'website');

        foreach ($modules as $module)
        {
                $this->getStyleService()
                    ->registerStyle('modules.' . $module . '.backoffice')
	                ->registerStyle('modules.' . $module . '.bindings');
        }
        
        $this->setAttribute('cssInclusion', $this->getStyleService()->execute(K::XUL));
		$this->getJsService()->registerScript('modules.uixul.lib.wToolkit');
        $this->setAttribute('scriptInclusion', $this->getJsService()->executeInline(K::XUL));   
		$rq->endI18nWork();
    }
}
