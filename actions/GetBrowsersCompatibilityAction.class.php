<?php
/**
 * uixul_GetBrowsersCompatibilityAction
 * @package modules.uixul.actions
 */
class uixul_GetBrowsersCompatibilityAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
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
			
			$langs = RequestContext::getInstance()->getSupportedLanguages();
			$result['langs'] = $langs;
			
			$uilangs = RequestContext::getInstance()->getUISupportedLanguages();
			$result['uilangs'] = $uilangs;
			
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