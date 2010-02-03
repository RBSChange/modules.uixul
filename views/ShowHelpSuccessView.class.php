<?php
class uixul_ShowHelpSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Show-Help', K::XUL, 'uixul');

		$lang = $request->getAttribute('lang');

		$this->setAttribute('lang', $lang);
		$this->setAttribute('moduleName', $request->getAttribute('moduleName'));
		$this->setAttribute('defaultContent', $request->getAttribute('defaultContent'));
		$this->setAttribute('helpContents', $request->getAttribute('helpContents'));
		$this->setAttribute('helpTitle', f_Locale::translate(
		    '&modules.uixul.bo.general.ShowHelpTitle;',
		    array('moduleName' => f_Locale::translate(
		        '&modules.' . $request->getAttribute('moduleName') . '.bo.general.Module-name;'
		    )),
		    $lang
		));
		$this->setAttribute('backTitle', f_Locale::translate('&modules.uixul.bo.general.BackHelpEllipsis;', null, $lang));
		$this->setAttribute('nextTitle', f_Locale::translate('&modules.uixul.bo.general.NextHelpEllipsis;', null, $lang));
		$this->setAttribute('homeTitle', f_Locale::translate('&modules.uixul.bo.general.HomeHelpEllipsis;', null, $lang));
		$this->setAttribute('printTitle', f_Locale::translate('&modules.uixul.bo.general.PrintHelpEllipsis;', null, $lang));

		$this->setAttribute('languages', $request->getAttribute('languages'));

        $this->setAttribute(
            'cssInclusion',
            $this->getStyleService()
                ->registerStyle('modules.uixul.backoffice')
                ->execute(K::XUL)
        );

		// include JavaScript
		$this->getJsService()->registerScript('modules.uixul.lib.default');
		$this->setAttribute('scriptInclusion', $this->getJsService()->executeInline(K::XUL));

		$rq->endI18nWork();
	}
}
