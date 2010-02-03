<?php
/**
 * uixul_LoadTagsAction
 * @package modules.uixul.actions
 */
class uixul_LoadTagsAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$ts = TagService::getInstance();
		
		$document = $this->getDocumentInstanceFromRequest($request);
		if ($document !== null)
		{
			$result = $ts->getAffectedTagsForDocument($document);
			$result['label'] = $document->getLabel();
		}
		else
		{
			$result = array();
		}
		
		return $this->sendJSON($result);
	}
}