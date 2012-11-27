<?php
/**
 * uixul_BlockOpenInBackofficeAction
 * @package modules.uixul.lib.blocks
 */
class uixul_BlockOpenInBackofficeAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition() || $this->isInBackofficePreview() || RequestContext::getInstance()->getUserAgentType() != 'gecko')
		{
			return website_BlockView::NONE;
		}
		
		// Show the block only in developement mode or if the user is logged in backoffice.
		if (!Framework::inDevelopmentMode() && !users_UserService::getInstance()->getCurrentBackEndUser())
		{
			return website_BlockView::NONE;
		}
		
		$link = new f_web_ChromeParametrizedLink('rbschange/content/ext/' . PROJECT_ID);
		$link->setQueryParameters(array('module' => 'uixul', 'action' => 'Admin', 'uilang' => null));
		$link->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
		$xchromeUrl = $link->getUrl();
		
		// Link to the page.
		$page = $this->getContext()->getPageDocument();
		if ($page instanceof website_persistentdocument_pagereference)
		{
			$page = website_persistentdocument_page::getInstanceById($page->getReferenceofid());
		}
		$request->setAttribute('page', $page);
		$modelName = $page->getPersistentModel()->getBackofficeName();
		$pageUrl = Framework::getUIBaseUrl() . '/admin#website,openDocument,' . $modelName . ',' . $page->getId();
		$request->setAttribute('pageUrl', $pageUrl);
		$pageXchromeUrl = $xchromeUrl . '#website,openDocument,' . $modelName . ',' . $page->getId();
		$request->setAttribute('pageXchromeUrl', $pageXchromeUrl);
	
		// Link to the detail document.
		$detailId = $this->getContext()->getDetailDocumentId();
		if ($detailId && $detailId !== $page->getId())
		{
			$document = DocumentHelper::getDocumentInstance($detailId);
			$editModule = uixul_DocumentEditorService::getInstance()->getEditModuleName($document);
			if ($editModule)
			{
				$request->setAttribute('detailDocument', $document);
				$modelName = $document->getPersistentModel()->getBackofficeName();
				$pageUrl = Framework::getUIBaseUrl() . '/admin#' . $editModule. ',openDocument,' . $modelName . ',' . $document->getId();
				$request->setAttribute('detailDocumentUrl', $pageUrl);
				$detailDocumentXchromeUrl = $xchromeUrl . '#' . $editModule. ',openDocument,' . $modelName . ',' . $document->getId();
				$request->setAttribute('detailDocumentXchromeUrl', $detailDocumentXchromeUrl);
			}
		}
		
		return website_BlockView::SUCCESS;
	}
}