<?php
class uixul_GetAdminStylesheetsAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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
		return View::NONE;
	}	

	private function renderStylesheets()
	{
		// include stylesheets
		$moduleService = ModuleService::getInstance();
		$modules = $moduleService->getModulesObj();
				
		$bs = uixul_BindingService::getInstance();
		$ss = website_StyleService::getInstance();
		$engine = $ss->getFullEngineName('xul');
		
		// Module backoffice styles :
		foreach ($modules as $cModule)
		{
			$module = $cModule->getName();
			$stylename = 'modules.' . $module . '.backoffice';
			echo "\n/* STYLE for module $stylename */\n";
			echo $ss->getCSS($stylename, $engine);
			
			$hasPerspective = $cModule->hasPerspectiveConfigFile();					
			if ($module === 'uixul' || $cModule->isEnabled())
			{	
				if (Framework::inDevelopmentMode())
				{
					echo "\n/* MozBindings for module $module BEGIN */\n";
				}
				
				echo "\n", $bs->getModules($module);
				if (!$hasPerspective)
				{
					echo "\n", $bs->getForms($module), "\n", $bs->getWidgets($module);				
				}
				else
				{
					echo "\n", uixul_DocumentEditorService::getInstance()->getCSSBindingForModule($module);
				}
				echo "\n", $bs->getBlocks($module), "\n";
				if (Framework::inDevelopmentMode())
				{
					echo "\n/* MozBindings for module $module END */\n\n";
				}
				
			}
			
			$stylename = 'modules.' . $module . '.bindings';		
			echo "\n/* BINDINGS for module $stylename */\n";
			echo $ss->getCSS($stylename, $engine);
		}
		
		if (RequestContext::getInstance()->getOperatingSystem() == RequestContext::OS_MAC)
		{
			echo  $ss->getCSS('modules.uixul.macoffice', $engine);
		}
	}
}