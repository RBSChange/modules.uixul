<?php
class uixul_GetDocumentListSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$lang = RequestContext::getInstance()->getLang();

		$this->setTemplateName(ucfirst(K::GENERIC_MODULE_NAME).'-Response', K::XML, K::GENERIC_MODULE_NAME);

		$contents = array();

		$documents = $request->getAttribute('documents');

		$deep = $request->getAttribute('deep');

		$lastLevel = 0;
		$lastParent = 0;
		foreach ($documents as $documentData)
		{
		    if (is_array($documentData))
		    {
				$document = $documentData['document'];
			    if (!$document->isLangAvailable($lang))
	            {
	                //continue;
	            }

			    $parent = $documentData['parent'];
			    $level = $documentData['level'];
				$id = $document->getId();
				if ($documentData['isFolder'])
				{
				    $icon = 'folder';
				}
				else
				{
				    $icon = $documentData['icon'];
				}
				$icon = $icon ? sprintf (' icon="%s"', MediaHelper::getIcon($icon, MediaHelper::SMALL)) : '';
				if ($deep && ($level > 0) && (($level != $lastLevel) || ($parent != $lastParent)))
				{
				    $parentDocument = $this->getDocumentService()->getDocumentInstance($parent);
				    $label = $parentDocument->isContextLangAvailable() ? $parentDocument->getLabel() : $parentDocument->getVoLabel();
				    $contents[] = sprintf('<document type="group" id="%s"%s><![CDATA[%s]]></document>', $parent, $icon, f_Locale::translateUI($label));
				}
				$lastLevel = $level;
				$lastParent = $parent;
		    }
		    else if ($documentData instanceof f_persistentdocument_PersistentDocument)
		    {
		    	$icon =  sprintf (' icon="%s"', MediaHelper::getIcon($documentData->getPersistentModel()->getIcon(), MediaHelper::SMALL)); 
		    	$id = $documentData->getId();
		    	$document = $documentData;
		    	$level = 1;
		    }
		    $label = $document->isContextLangAvailable() ? $document->getLabel() : $document->getVoLabel();
			$contents[] = sprintf('<document id="%s"%s><![CDATA[%s]]></document>', $id, $icon, str_repeat (' ', min(1, $level) * 4) . f_Locale::translateUI($label));
		}

		$this->setAttribute('status', 'OK');
		$this->setAttribute('id', $request->getAttribute('listid'));
		$this->setAttribute('lang', $lang);
		$this->setAttribute('contents', join(K::CRLF, $contents));
	}
}