<?php
class uixul_ShowHelpContentAction extends f_action_BaseAction
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

		$contentName = $request->getParameter('content');

        $pathWhereToFindHelp = Resolver::getInstance('file')
            ->setPackageName('modules_'.$moduleName)
            ->setDirectory('doc/help')
            ->getPath('help.xml');

        if ($pathWhereToFindHelp)
        {
    		$helpContents = array();
            $helpConfig = f_object_XmlObject::getInstanceFromFile($pathWhereToFindHelp)->getRootElement();

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

			$request->setAttribute('lang', $lang);

            foreach ($helpConfig->content as $helpContent)
            {
                if ((string)$helpContent['name'] == $contentName)
                {
                    $fileName = (string)$helpContent['fileName'];

                    try
                    {
                        $template = Loader::getInstance('template')
    								->setPackageName('modules_' . $moduleName)
    								->setMimeContentType(K::HTML)
    								->setDirectory('doc/help')
    								->load($fileName . '-' . $lang);

                        $request->setAttribute('helpContent', $template->execute());
                    }
                    catch (TemplateNotFoundException $e)
                    {                    	
                        $request->setAttribute('helpContent', f_Locale::translate('&modules.uixul.bo.general.HelpNotFound;'));
                    }
                    break;
                }
            }
        }

        $rq->endI18nWork();

		return View::SUCCESS;
	}
}