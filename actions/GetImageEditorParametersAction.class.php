<?php

class uixul_GetImageEditorParametersAction extends change_JSONAction 
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$infos = null;
		try 
		{
			$lang = RequestContext::getInstance()->getLang();
			$document = DocumentHelper::getDocumentInstance($request->getParameter('cmpref'));
			$infos = $document->getCommonInfo();
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return $this->sendJSON(array('infos' => $infos));
	}
}