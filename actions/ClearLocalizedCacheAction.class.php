<?php
class uixul_ClearLocalizedCacheAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		CacheService::getInstance()->clearLocalizedCache();
		return $this->getSuccessView();
	}
}