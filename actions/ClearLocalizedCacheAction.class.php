<?php
class uixul_ClearLocalizedCacheAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		CacheService::getInstance()->clearLocalizedCache();
		return $this->getSuccessView();
	}
}