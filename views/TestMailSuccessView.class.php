<?php
class uixul_TestMailSuccessView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Uixul-TestMail-Success', 'xul');

		// Module backoffice styles :
		$ss = website_StyleService::getInstance();
		$ss->registerStyle('modules.uixul.backoffice');
		$cssInclusion = $ss->execute('xul');
		$this->setAttribute('cssInclusion', $cssInclusion);
	}
}