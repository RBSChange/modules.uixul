<?php

class uixul_TestMailSuccessView extends f_view_BaseView
{

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Uixul-TestMail-Success', K::XUL);

		// Module backoffice styles :
		$ss = website_StyleService::getInstance();
		$ss->registerStyle('modules.uixul.backoffice');
        $cssInclusion = $ss->execute(K::XUL);
        $this->setAttribute('cssInclusion', $cssInclusion);
	}

}