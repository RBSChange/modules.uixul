<?php
class generic_TestWControllerSuccessView extends TemplateView
{

	public function execute()
	{
		$context = $this->getContext();
		$request = $context->getRequest();
		$this->setConfig('modules_'.K::GENERIC_MODULE_NAME, '/config/display.xml');
		$this->setCurrentDisplayName('XmlResponse');

		// Always modified
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		// Date in the past
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		// HTTP/1.0
		header("Pragma: no-cache");

		$this->setAttribute('status', 'OK');
		$this->setAttribute('message', 'Action success.');

		$this->setAttribute('module', $request->getParameter(AG_MODULE_ACCESSOR));
		$this->setAttribute('action', $request->getParameter(AG_ACTION_ACCESSOR));
	}

}