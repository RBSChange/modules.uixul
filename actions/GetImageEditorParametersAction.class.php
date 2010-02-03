<?php

class uixul_GetImageEditorParametersAction extends f_action_BaseJSONAction 
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$infos = null;
		try 
		{
			$document = DocumentHelper::getDocumentInstance($request->getParameter('cmpref'));
			$infos = $document->getInfo();
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return $this->sendJSON(array('infos' => $infos));
	}
}