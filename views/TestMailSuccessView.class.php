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
		$this->getStyleService()->registerStyle('modules.uixul.backoffice');
        $cssInclusion = $this->getStyleService()->execute(K::XUL);
        $this->setAttribute('cssInclusion', $cssInclusion);
	}

}