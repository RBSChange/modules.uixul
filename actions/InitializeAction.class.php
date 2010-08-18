<?php
class uixul_InitializeAction extends f_action_BaseAction
{
    /**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
    	f_persistentdocument_PersistentProvider::getInstance()->clearFrameworkCache();
    	//f_SimpleCache::clear();
    	f_DataCacheService::getInstance()->clearAll();

        f_util_FileUtils::clearDir(f_util_FileUtils::buildCachePath('template'));
        f_util_FileUtils::clearDir(f_util_FileUtils::buildWebCachePath('binding'));
        f_util_FileUtils::clearDir(f_util_FileUtils::buildWebCachePath('css'));
        f_util_FileUtils::clearDir(f_util_FileUtils::buildWebCachePath('js'));
        f_util_FileUtils::clearDir(f_util_FileUtils::buildWebCachePath('htmlpreview'));
     
        return $this->getSuccessView();
    }
}