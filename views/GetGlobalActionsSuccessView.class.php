<?php
class uixul_GetGlobalActionsSuccessView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$lang = RequestContext::getInstance()->getLang();

		$this->setTemplateName(ucfirst('generic').'-Response', 'xml', 'generic');
		$contentArray = array();
		foreach ($request->getAttribute('globalActionArray') as $actionId => $actionObject)
		{
			$contentArray[] = '<globalAction name="'.$actionId.'" label="'. $actionObject->label . '" icon="'. $actionObject->icon . '" hasSeparator="' . var_export($actionObject->hasSeparator, true) . '" />';
		}
		$this->setAttribute('status', 'OK');
		$this->setAttribute('lang', $lang);
		$this->setAttribute('contents', join(PHP_EOL, $contentArray));
	}
}