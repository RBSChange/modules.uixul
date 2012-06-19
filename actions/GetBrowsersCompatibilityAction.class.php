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
			
			$backEndGroupID = users_BackendgroupService::getInstance()->getBackendGroupId(); 
			$users = users_UserService::getInstance()->getRootUsersByGroupId($backEndGroupID);

			foreach ($users as $user) 
			{
				/* @var $user users_persistentdocument_user */
				if ($user->getEmail() === null)
				{
					$result['firstlogin'] = $user->getLogin();
				}
				else
				{
					break;
				}
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