<?php
class uixul_GetCacheVersionAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		change_Controller::setNoCache();
		$cacheVersion = $this->getPersistentProvider()->getSettingValue('modules_uixul', 'cacheVersion');
		users_UserService::getInstance()->pingBackEndUser();
		if ($cacheVersion === null)
		{
			$cacheVersion = 0;
		}
		return $this->sendJSON(array('cacheVersion' => $cacheVersion));
	}
}