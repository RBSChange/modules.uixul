<?php
class uixul_GetStylesheetAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		if (!headers_sent())
		{
			header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
		}
		
		$content = array();
		$moduleName = $this->getModuleName($request);
		
		$bs = uixul_BindingService::getInstance();
		
		$content[] = $bs->getForms($moduleName);

		try
		{
			$content[] = $bs->getWidgets($moduleName);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}

		$content[] = $bs->getModules($moduleName);

		$content[] = $bs->getBlocks($moduleName);

		$request->setAttribute('contents', join(K::CRLF, $content));

		// TODO intbonjf 2006-03-30: cache the content in a file
		return change_View::SUCCESS;
	}
}