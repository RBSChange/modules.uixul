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

		$this->setTemplateName(ucfirst(K::GENERIC_MODULE_NAME).'-Response', K::XML, K::GENERIC_MODULE_NAME);
		$contentArray = array();
		foreach ($request->getAttribute('globalActionArray') as $actionId => $actionObject)
		{
			$contentArray[] = '<globalAction name="'.$actionId.'" label="'. $actionObject->label . '" icon="'. $actionObject->icon . '" hasSeparator="' . var_export($actionObject->hasSeparator, true) . '" />';
		}
		$this->setAttribute('status', 'OK');
		$this->setAttribute('lang', $lang);
		$this->setAttribute('contents', join(K::CRLF, $contentArray));
	}
}