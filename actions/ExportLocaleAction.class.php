<?php
class uixul_ExportLocaleAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$moduleName = $request->getParameter('mod');
		$path = $request->getParameter('path');
		$userEdited = ($request->hasParameter('useredited') && (intval($request->getParameter('useredited')) == 1));
		
		$document = new DOMDocument('1.0', 'utf-8');
		$documentRoot = $document->createElement('localization');
		$document->appendChild($documentRoot);
				
		$locales = $this->getPersistentProvider()->getLocalesByPath($path);
		foreach ($locales as $key => $locale)
		{
			foreach ($locale as $lang => $data)
			{
				if (($userEdited && !$data['useredited']) || !$data['content'] || $key == $path)
				{
					unset($locale[$lang]);
				}
			}
			
			if (count($locale) > 0)
			{
				$entity = $document->createElement('entity');
				$entity->setAttribute('id', substr($key, strlen($path)+1));
				foreach ($locale as $lang => $data)
				{
					$localeNode = $document->createElement('locale');
					$localeNode->setAttribute('lang', $lang);
					$localeNode->appendChild($document->createTextNode($data['content']));
					$entity->appendChild($document->createTextNode("\n\t\t"));
					$entity->appendChild($localeNode);
				}
				$entity->appendChild($document->createTextNode("\n\t"));
				$documentRoot->appendChild($document->createTextNode("\n\t"));
				$documentRoot->appendChild($entity);
				$documentRoot->appendChild($document->createTextNode("\n"));
			}
		}
		
		// TODO: Here the utf8_encode() is required as if is already in UTF-8. Somewhere next
		// (I didn't find where), the result is decoded, so with this, it ends with a proper
		// UTF-8 string... That decoding should be removed, then this utf8_encode() should be
		// removed too.   
		$request->setAttribute('contents', '<package><![CDATA[' . utf8_encode($document->saveXml()) . ']]></package>');
		
		return $this->getSuccessView();
	}
}