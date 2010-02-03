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
    	f_SimpleCache::clear();

        f_util_FileUtils::clearDir(AG_CACHE_DIR . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR);

        f_util_FileUtils::clearDir(WEBAPP_HOME . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'binding' . DIRECTORY_SEPARATOR);
        f_util_FileUtils::clearDir(WEBAPP_HOME . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR);
        f_util_FileUtils::clearDir(WEBAPP_HOME . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR);
        f_util_FileUtils::clearDir(WEBAPP_HOME . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'htmlpreview' . DIRECTORY_SEPARATOR);
     
        return $this->getSuccessView();
    }
}