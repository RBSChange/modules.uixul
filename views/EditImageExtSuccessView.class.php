<?php
class uixul_EditImageExtSuccessView extends f_view_BaseView
{

    /**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

        // Set our template
        $this->setTemplateName('Uixul-EditImageExt-Success', K::XUL);

        $modules = array('generic', 'uixul', 'website');
        foreach ($modules as $module)
        {
           $this->getStyleService()
                    ->registerStyle('modules.' . $module . '.backoffice')
	                ->registerStyle('modules.' . $module . '.bindings');
        }
        $this->setAttribute('cssInclusion', $this->getStyleService()->execute(K::XUL));
        
		$this->getJsService()->registerScript('modules.uixul.lib.default');
        $this->setAttribute('scriptInclusion', $this->getJsService()->executeInline(K::XUL));

        $languages = array();
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
		{
		    $languages[$lang] = array(
		        'label' => f_Locale::translateUI('&modules.uixul.bo.languages.' . ucfirst($lang) . ';'),
		        'value' => $lang
		    );
		}
		$this->setAttribute('languages', $languages);
		$rq->endI18nWork();

    	$documentId = $request->getParameter('cmpref');
		if (is_numeric($documentId) && $documentId > 0)
		{
			try 
			{
				$infos = JsonService::getInstance()->encode(DocumentHelper::getDocumentInstance($documentId)->getInfo());
			}
			catch (Exception $e)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::exception($e);
				}
				$infos = '{}';
			}
			$this->setAttribute('documentId', $documentId);
			$this->setAttribute('infos', $infos);
		}
		else
		{
			$this->setAttribute('documentId', 'null');
			$this->setAttribute('infos', '{}');			
		}
    }
}
