<?php
class uixul_CheckLocaleKeyAction extends f_action_BaseAction
{
    /**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        $provider = f_persistentdocument_PersistentProvider::getInstance();

        $key = trim(str_replace('..', '.', preg_replace('/[^a-z0-9_-]/i', '.', trim($request->getParameter('key')))));

        $key = trim(str_replace('..', '.', $key));

        while (!preg_match('/^[a-z0-9]$/i', substr($key, -1)))
        {
            $key = trim(substr($key, 0, strlen($key) - 1));
        }

        while (!preg_match('/^[a-z0-9]$/i', substr($key, 0, 1)))
        {
            $key = trim(substr($key, 1));
        }

        $request->setAttribute('message', $key);

        if ($provider->checkTranslateKey($key))
        {
            return $this->getErrorView();
        }

        return $this->getSuccessView();
    }
}