<?php
class uixul_ShowHelpAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$moduleName = $this->getModuleName($request);

        $request->setAttribute('moduleName', $moduleName);

		$pathWhereToFindHelp = Resolver::getInstance('file')
		  ->setPackageName('modules_'.$moduleName)
		  ->setDirectory('doc/help')
		  ->getPath('help.xml');

        if ($pathWhereToFindHelp)
        {
    		$helpContents = array();
            $helpConfig = f_object_XmlObject::getInstanceFromFile($pathWhereToFindHelp)
                ->getRootElement();
            $defaultContent = (string)$helpConfig['defaultContent'];
            if ($defaultContent)
            {
                $request->setAttribute('defaultContent', $defaultContent);
            }

            $languages = (string)$helpConfig['languages'];
            if ($languages)
            {
                $languages = explode(' ', $languages);
                $flags = array();
                foreach ($languages as $language)
                {
                    $language = trim($language);
                    $flags[] = array(
                        'lang' => $language,
                        'label' => f_Locale::translate('&modules.uixul.bo.languages.' . ucfirst(strtolower($language)) . ';', null, $language)
                    );
                }
                $request->setAttribute('languages', $flags);
            }

            if ($request->hasParameter(K::LANG_ACCESSOR))
			{
			    $lang = $request->getParameter(K::LANG_ACCESSOR);
			}
			else if ($request->hasParameter(K::COMPONENT_LANG_ACCESSOR))
			{
			    $lang = $request->getParameter(K::COMPONENT_LANG_ACCESSOR);
			}
			else
			{
			    $lang = RequestContext::getInstance()->getUILang();
			}

			if (!$languages || ($languages && !in_array($lang, $languages)))
			{
			    $lang = RequestContext::getInstance()->getUILang();
			}

			$request->setAttribute('lang', $lang);

            $helpContents = array();
            $index = 1;
            foreach ($helpConfig->content as $helpContent)
            {
                if ((string)$helpContent['title-' . $lang])
                {
                    $title = (string)$helpContent['title-' . $lang];
                }
                else
                {
                    $title = f_Locale::translate((string)$helpContent['title'], null, $lang);
                }
                $helpContents[(string)$helpContent['name']] = array(
                    'title' => sprintf(
                        '%d. %s',
                        $index++,
                        $title
                    ),
                    'fileName' => (string)$helpContent['fileName']
                );
            }
            $request->setAttribute('helpContents', $helpContents);
        }

        $rq->endI18nWork();

        return View::SUCCESS;
	}
}