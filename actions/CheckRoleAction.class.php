<?php
class uixul_CheckRoleAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		try
		{
			 f_permission_PermissionService::getInstance()->checkPermission(
				users_UserService::getInstance()->getCurrentBackEndUser(),
				$request->getParameter('role'), $request->getParameter('node'));
		}
		catch (Exception $e)
		{
			return $this->sendJSONException($e);
		}
		return $this->sendJSON(array('role' => $request->getParameter('role'), 'node' => $request->getParameter('node')));
	}
}
