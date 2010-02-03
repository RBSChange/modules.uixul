<?php
class uixul_GetGlobalActionsAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		// Retrieve request data
		$moduleName = $this->getModuleName($request);
		$moduleActions = array();
		
		uixul_lib_UiService::getModuleActions('uixul', $moduleActions);
		$baseActions = $moduleActions;
		
		uixul_lib_UiService::getModuleActions($moduleName, $moduleActions);
		
		$globalActionArray = array();
		$globalBaseActionArray = array();
		
		// Permission stuff
		$ps = f_permission_PermissionService::getInstance();
		$nodeId = ModuleService::getInstance()->getRootFolderId($moduleName);
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		foreach ($moduleActions as $actionId => $actionObject)
		{
			if ($actionObject->global)
			{
				if ($ps->hasAccessToBackofficeAction($user, $moduleName, $actionId, $nodeId))
				{
					if (isset($baseActions[$actionId]))
					{
						$globalBaseActionArray[$actionId] = $actionObject;
					}
					else
					{
						$globalActionArray[$actionId] = $actionObject;
					}
				}
			}
		}
		
		$request->setAttribute('globalActionArray', array_merge($globalActionArray, $globalBaseActionArray));
		return View::SUCCESS;
	}
}