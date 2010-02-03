<?php
class uixul_GetDocumentArrayAction extends f_action_BaseAction
{

	
	/*
	 xul_controller.php?module=uixul&action=GetDocumentArray&cmpref=10011&property=
	*/
		
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$document = $this->getDocumentInstanceFromRequest($request);
		$propertyName = $request->getParameter("property");
		$model = $document->getPersistentModel();
		$property = $model->getProperty($propertyName);
		if ($property->isArray())
		{
			$children = $document->{'get'.ucfirst($propertyName).'Array'}();
		}
		else
		{
			$children = array();
		}
		header('Content-Type' . ':' . 'text/xml');
		$this->write($document, $children);
		return View::NONE;
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
			$this->writeDocument($output, $document);
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
		$output->writeElement('action', 'GetDocumentArray');
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
