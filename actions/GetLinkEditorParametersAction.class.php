<?php

class uixul_GetLinkEditorParametersAction extends f_action_BaseJSONAction 
{
	/**
	 * @param Context $context
	 * @param Request $request
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
