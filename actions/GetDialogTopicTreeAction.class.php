<?php
class uixul_GetDialogTopicTreeAction extends f_action_BaseAction
{
	
	/*
	 xul_controller.php?module=uixul&action=GetDialogTree&wemod=website&cmpref=10011
	*/
	
	/**
	 * @param Request $request
	 * @return array<integer>
	 */
	protected function getDocumentIdArrayFromRequest($request)
	{
		$cmpref = intval($request->getParameter('cmpref'));
		if ($cmpref <= 0)
		{
			$cmpref = ModuleService::getInstance()->getRootFolderId($this->getModuleName($request));
		}
		return array($cmpref);
	}
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->moduleName = $this->getModuleName($request);
		
		$document = $this->getDocumentInstanceFromRequest($request);
		if ($this->hasPermission($document))
		{
			$children = $this->getChildren($document);
		}
		else
		{
			$children = array();
		}
		header('Content-Type' . ':' . 'text/xml');
		$this->write($document, $children);
		return View::NONE;
	}
	
	private $moduleName = null;
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function getChildren($document)
	{
		if ($document instanceof generic_persistentdocument_rootfolder)
		{
			return $document->getDocumentService()->getChildrenOf($document, 'modules_website/website');
		}
    	else if ($document instanceof website_persistentdocument_website)
    	{
    		return $document->getDocumentService()->getChildrenOf($document, 'modules_website/topic');
    	}
		else if ($document instanceof website_persistentdocument_topic)
    	{
    		return $document->getDocumentService()->getChildrenOf($document, 'modules_website/topic');
    	}
    	return array();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	private function hasPermission($document)
	{
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		$permission = 'modules_' . $this->moduleName . '.List.' . $document->getPersistentModel()->getDocumentName();
		return f_permission_PermissionService::getInstance()->hasPermission($user, $permission, $document->getId());
	}
	
	/**
	 * @var XMLWriter
	 */
	private $output;
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $children
	 */
	private function write($document, $children)
	{
		$output = new XMLWriter();
		$output->openMemory();
		$this->writeHeader($output, $document);
		foreach ($children as $document)
		{
			if ($this->hasPermission($document))
			{
				$this->writeDocument($output, $document);
			}
		}
		
		$this->writeFooter($output);
		echo $output->outputMemory(true);
	}
	
	/**
	 * @param XMLWriter $output
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function writeHeader($output, $document)
	{
		$output->startDocument('1.0', 'UTF-8');
		$output->startElement('response');
		$output->writeElement('action', 'GetDialogTopicTree');
		$output->writeElement('module', 'uixul');
		$output->writeElement('status', 'OK');
		$output->writeElement('id', $document->getId());
		$output->writeElement('lang', RequestContext::getInstance()->getLang());
		$output->writeElement('workinglang', RequestContext::getInstance()->getUILang());
		$output->writeElement('label', $document->getLabel());
		$output->startElement('nodes');
	}
	
	/**
	 * @param XMLWriter $output
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function writeDocument($output, $document)
	{
		$model = $document->getPersistentModel();
		$output->startElement('nodeitem');
		$output->writeAttribute('id', $document->getId());
		$output->writeAttribute('label', $document->getTreeNodeLabel());
		$output->writeAttribute('model', str_replace('/', '_', $model->getName()));
		$output->endElement();
	}
	/**
	 * @param XMLWriter $output
	 */
	private function writeFooter($output)
	{
		$output->endElement(); //children
		$output->endElement(); //response
		$output->endDocument(); //DOCUMENT
	}
}
