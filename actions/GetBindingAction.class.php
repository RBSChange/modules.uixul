<?php
class uixul_GetBindingAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + 28800) . " GMT");
		header("Cache-Control:");
		header("Pragma:");
		$rq = RequestContext::getInstance();
		$rq->setUILangFromParameter($request->getParameter('uilang'));
		$rq->setLang($rq->getUILang());		
		$binding = $request->getParameter('binding');
		$bindingPathInfo = explode('.', $binding);
		if ($bindingPathInfo[0] === 'modules')
		{
			if (count($bindingPathInfo) == 2)
			{
				$config = uixul_ModuleBindingService::getInstance()->loadConfig($bindingPathInfo[1]);
				if ($config)
				{
					echo uixul_ModuleBindingService::getInstance()->buildModuleBinding($bindingPathInfo[1], $config);
				}
				return change_View::NONE;
			}
			else if (count($bindingPathInfo) == 4)
			{
				switch ($bindingPathInfo[2])
				{
					case 'editors' :
						echo uixul_DocumentEditorService::getInstance()->getEditorsBinding(
								$bindingPathInfo[1], $bindingPathInfo[3]);
						return change_View::NONE;
					case 'block' :
						echo uixul_PropertyGridBindingService::getInstance()->getBinding(
								$bindingPathInfo[1], $bindingPathInfo[3]);
						return change_View::NONE;
				}
			}
		}
		echo uixul_BindingService::getInstance()->buildBinding($binding);
		return change_View::NONE;
	}
	
	public function getRequestMethods()
	{
		return change_Request::GET;
	}
}