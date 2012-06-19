<?php
class uixul_GetAdminStylesheetsAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
		header('Content-type: text/css');
		$rq = RequestContext::getInstance();
		$rq->setUILangFromParameter($request->getParameter('uilang'));
		$rq->beginI18nWork($rq->getUILang());
		$this->renderStylesheets();
		$rq->endI18nWork();		
		return change_View::NONE;
	}	

	private function renderStylesheets()
	{
		// include stylesheets
		$moduleService = ModuleService::getInstance();
		$modules = $moduleService->getModulesObj();
				
		$bs = uixul_BindingService::getInstance();
		$ss = website_StyleService::getInstance();
		$engine = $ss->getFullEngineName('xul');
		
		$iconsstylename = 'modules.uixul.documenticons';
		echo $ss->getCSS($iconsstylename, $engine);
		
		// Module backoffice styles :
		foreach ($modules as $cModule)
		{
			/* @var $cModule c_Module */
			$module = $cModule->getName();
			echo "\n/* STYLE for module $module BEGIN */\n";
			
			$stylename = 'modules.' . $module . '.backoffice';
			echo $ss->getCSS($stylename, $engine);
			
			$stylename = 'modules.' . $module . '.bindings';		
			echo $ss->getCSS($stylename, $engine);
			
			if ($cModule->isVisible())
			{				
				echo "\n", $bs->getModules($module);
				echo "\n", uixul_DocumentEditorService::getInstance()->getCSSBindingForModule($module);
				echo "\n", $bs->getBlocks($module), "\n";
			}
			
			echo "\n/* STYLE for module END BEGIN */\n";

		}
		
		if (RequestContext::getInstance()->getOperatingSystem() == RequestContext::OS_MAC)
		{
			echo  $ss->getCSS('modules.uixul.macoffice', $engine);
		}
	}
}