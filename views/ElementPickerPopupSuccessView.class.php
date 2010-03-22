<?php
class uixul_ElementPickerPopupSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
	    $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Element-Picker-Popup', K::XUL);

		if ($request->hasParameter(K::COMPONENT_ACCESSOR))
		{
			$documents = $request->getParameter(K::COMPONENT_ACCESSOR);
			if ($documents == "all" || $documents == "*")
			{
				$documents = array();
				$availableModules = ModuleService::getInstance()->getModules();
				foreach ($availableModules as $availableModuleName)
				{
				    $availableShortModuleName = substr($availableModuleName, strpos($availableModuleName, '_') + 1);
		            if (defined('MOD_' . strtoupper($availableShortModuleName) . '_VISIBLE')
				    && (constant('MOD_' . strtoupper($availableShortModuleName) . '_VISIBLE') == true))
				    {
				    	$documents[] = $availableModuleName;
				    }
				}
			}
			else if ( ! is_array($documents) )
			{
				$documents = explode(",", $documents);
			}
		}
		else if ($request->hasParameter(K::WEBEDIT_MODULE_ACCESSOR))
		{
			$documents = array("modules_" . $request->getParameter(K::WEBEDIT_MODULE_ACCESSOR));
		}

		$documentTypesPerModule = array();
		$availableModules = array();
		// guess module names from document types
		foreach ($documents as $document)
		{
			if (preg_match('#modules_([a-z]+)([_/]([a-z]+))?#', $document, $matches))
			{
				$module = $matches[1];
				if ( ! isset($documentTypesPerModule[$module]) )
				{
					$documentTypesPerModule[$module] = array();
				}
				if (isset($matches[3]))
				{
					if ($matches[3] == "folder")
					{
						$type = "modules_".K::GENERIC_MODULE_NAME ."_folder";
					}
					else
					{
						$type = "modules_".$module."_".$matches[3];
					}
					$documentTypesPerModule[$module][] = $type;
				}
				else
				{
					$docs = ModuleService::getInstance()->getDefinedDocuments($module);
					foreach ($docs as $doc)
					{
						$type = "modules_".$module."_".$doc;
						$documentTypesPerModule[$module][] = $type;
					}
					$documentTypesPerModule[$module][] = "modules_".K::GENERIC_MODULE_NAME."_folder";
					$documentTypesPerModule[$module][] = "modules_".K::GENERIC_MODULE_NAME."_rootfolder";
				}
			}
		}
		$availableModules = array_keys($documentTypesPerModule);

		foreach ($availableModules as $availableModuleName)
		{
		    $modules[] = $availableModuleName;
		}

        // Module backoffice styles :
		foreach ($modules as $module)
        {
	        $this->getStyleService()
	           ->registerStyle('modules.' . $module . '.backoffice')
	           ->registerStyle('modules.' . $module . '.bindings');
        }
		$this->getStyleService()
	        ->registerStyle('modules.' . K::GENERIC_MODULE_NAME  . '.backoffice')
    		->registerStyle('modules.' . K::GENERIC_MODULE_NAME  . '.bindings');

        $cssInclusion = $this->getStyleService()->execute(K::XUL);

        $moduleDecks = array();
        $moduleButtons = array();

        // Module widgets :
		foreach ($modules as $module)
        {
	        if (defined('MOD_' . strtoupper($module) . '_VISIBLE')
		    && (constant('MOD_' . strtoupper($module) . '_VISIBLE') == true))
		    {
		        $link = LinkHelper::getUIChromeActionLink('uixul', 'GetStylesheet')
		        	->setQueryParametre('uilang', RequestContext::getInstance()->getUILang())
		        	->setQueryParametre('wemod', $module);
                $cssInclusion .= "\n" . '<?xml-stylesheet href="' . $link->getUrl() . '" type="text/css"?>';

				$moduleDecks[] = array(
					"id"         => "wselector_".$module,
					"name"       => $module,
					"selector"   => "default",
					"components" => join(" ", $documentTypesPerModule[$module])
					);

                if (defined('MOD_' . strtoupper($module) . '_ICON'))
    		    {
                    $icon = constant('MOD_' . strtoupper($module) . '_ICON');
    		    }
    		    else
    		    {
    		        $icon = 'component';
    		    }
    		    $icon = MediaHelper::getIcon($icon, MediaHelper::SMALL);

    		    $label = f_Locale::translate('&modules.' . $module . '.backoffice.ModuleName;');
    	        $moduleButtons[$label] = array(
    	           "elementid" => "wselector_".$module,
    	           'command'   => "switchSelector('" . $module . "')",
    	           "label"     => $label,
    	           "icon"      => $icon
    	        );
		    }
        }

        $this->setAttribute('modules', $moduleButtons);
        $this->setAttribute('moduleDecks', $moduleDecks);
        if (count($moduleDecks) > 1)
        {
        	$this->setAttribute("hasMoreThanOneModule", true);
        }
        else
        {
        	$this->setAttribute("hasMoreThanOneModule", false);
        }

        // intbonjf - 2006-05-05:
        // the GetStylesheet stylesheet of the generic module must be included
        $link = LinkHelper::getUIChromeActionLink('uixul', 'GetStylesheet')
        	->setQueryParametre('uilang', RequestContext::getInstance()->getUILang());
	    $cssInclusion .= "\n" . '<?xml-stylesheet href="' . $link->getUrl() . '" type="text/css"?>';

        $this->setAttribute('cssInclusion', $cssInclusion);

		$this->getJsService()->registerScript('modules.uixul.lib.default');
        
		$this->setAttribute(
            'scriptInclusion',
            $this->getJsService()->executeInline(K::XUL)
		);

		$rq->endI18nWork();
	}
}
