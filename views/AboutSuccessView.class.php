<?php
class uixul_AboutSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
		$this->setTemplateName('Uixul-About', K::HTML);
		$this->setAttributes($request->getParameters());
	 }
}