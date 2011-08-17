<?php
class uixul_AboutSuccessView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
    {
		$this->setTemplateName('Uixul-About', 'html');
		$this->setAttributes($request->getParameters());
	 }
}