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
        $rq->beginI18nWork($rq->getUILang());
		$this->renderStylesheets();
		$rq->endI18nWork();		
		return View::NONE;
	}
	
	/**
	 * Returns the StyleService instance to use within this view.
	 *
	 * @return StyleService
	 */
	private function getStyleService()
	{
		return StyleService::getInstance();
	}	

	private function renderStylesheets()
	{
		// include stylesheets
		$modules = array();
		$moduleService = ModuleService::getInstance();
		$availableModules = $moduleService->getModules();
		
		foreach ($availableModules as $availableModule)
		{
			$moduleName = $moduleService->getShortModuleName($availableModule);
			$modules[] = $moduleName;
		}
		
		$bs = uixul_BindingService::getInstance();
		$ss = StyleService::getInstance();
		$engine = $ss->getFullEngineName('xul');
		
		
		// Module backoffice styles :
		foreach ($modules as $module)
		{
			$stylename = 'modules.' . $module . '.backoffice';
			echo "\n/* STYLE for module $stylename */\n";
			echo $ss->getCSS($stylename, $engine);
		
			echo "\n/* BINDINGS for module $stylename */\n";
			$stylename = 'modules.' . $module . '.bindings';		
			echo  $ss->getCSS($stylename, $engine);
			
						
			if ($module === 'uixul' || defined('MOD_' . strtoupper($module) . '_ENABLED'))
			{	
				if (Framework::inDevelopmentMode())
				{
					echo "\n/* MozBindings for module $module BEGIN */\n";
				}
				
				echo "\n";
				echo $bs->getModules($module);
				echo "\n";
				echo $bs->getForms($module);
				echo "\n";
				echo uixul_DocumentEditorService::getInstance()->getCSSBindingForModule($module);
				echo "\n";			
				echo $bs->getWidgets($module);
				echo "\n";
				echo $bs->getBlocks($module);
				echo "\n";
				if (Framework::inDevelopmentMode())
				{
					echo "\n/* MozBindings for module $module END */\n\n";
				}
				
			}
		}
		
		if (RequestContext::getInstance()->getOperatingSystem() == RequestContext::OS_MAC)
		{
			echo  $ss->getCSS('modules.uixul.macoffice', $engine);
		}
	}
}