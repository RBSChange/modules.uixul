<?php
class uixul_CheckRoleAction extends f_action_BaseAction
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
				$request->getParameter('role'),
				$request->getParameter('node')
				);
		}
		catch (Exception $e)
		{
			// The role name may be invalid.
			Framework::exception($e);
			$this->setException($request, $e);
			return self::getErrorView();
		}

		return self::getSuccessView();
	}
}
