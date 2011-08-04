<?php

class uixul_GetLinkEditorParametersAction extends change_JSONAction 
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$url = null;
		try 
		{
			$document = DocumentHelper::getDocumentInstance($request->getParameter('cmpref'));
			$url = LinkHelper::getDocumentUrl($document);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return $this->sendJSON(array('url' => $url));
	}
}
