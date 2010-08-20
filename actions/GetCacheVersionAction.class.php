<?php
class uixul_GetCacheVersionAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		controller_ChangeController::setNoCache();
		$cacheVersion = $this->getPersistentProvider()->getSettingValue('modules_uixul', 'cacheVersion');
		if ($cacheVersion === null)
		{
			$cacheVersion = 0;
		}
		return $this->sendJSON(array('cacheVersion' => $cacheVersion));
	}
}