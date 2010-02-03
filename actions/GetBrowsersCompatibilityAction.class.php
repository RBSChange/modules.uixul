<?php
/**
 * uixul_GetBrowsersCompatibilityAction
 * @package modules.uixul.actions
 */
class uixul_GetBrowsersCompatibilityAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		try 
		{
			$result = Framework::getConfiguration('browsers');
			$result['uiprotocol'] = DEFAULT_UI_PROTOCOL;
			$admin = users_UserService::getInstance()->getBackEndUserByLogin('wwwadmin');
			if (!$admin || $admin->getEmail() == NULL)
			{
				$result['firstlogin'] = $admin->getLogin();
			}
		}
		catch (Exception $e)
		{
			return $this->sendJSONException($e);
		}
		return $this->sendJSON($result);		
	}
	
	/**
	 * @see f_action_BaseAction::isSecure()
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}