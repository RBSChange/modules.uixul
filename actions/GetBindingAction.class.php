<?php
class uixul_GetBindingAction extends f_action_BaseAction
{
	const FRAMEWORK_BINDING_SUCCESS_VIEW = 'Core';
	const CACHED_VIEW                    = 'Cached';
	const MODULE_BINDING_SUCCESS_VIEW    = 'Module';
	const FORM_BINDING_SUCCESS_VIEW      = 'Form';

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
		
	    $rq = RequestContext::getInstance();
	    try 
	    {
        	$rq->beginI18nWork($rq->getUILang());

			$binding = $request->getParameter('binding');
	
			$bindingPathInfo = explode('.', $binding);	
			if ($bindingPathInfo[0] === 'modules')
			{
				if (count($bindingPathInfo) == 2)
				{
					$moduleName = $bindingPathInfo[1];
					echo uixul_lib_UiService::buildModuleBinding($moduleName);
					$viewName = View::NONE;
				}
				else if (count($bindingPathInfo) == 4)
				{
					switch ($bindingPathInfo[2])
					{
						case 'form':
							$viewName = $this->getFormBinding($context, $request);
							break;
						case 'editors':
							echo uixul_DocumentEditorService::getInstance()->getEditorsBinding($bindingPathInfo[1], $bindingPathInfo[3]);
							$viewName = View::NONE;
							break;
						case 'block':
							echo uixul_PropertyGridBindingService::getInstance()->getBinding($bindingPathInfo[1], $bindingPathInfo[3]);
							$viewName = View::NONE;
							break;
						default:
							$viewName = $this->getBinding($context, $request);
							break;
					}
				}
				else
				{
					$viewName = $this->getBinding($context, $request);
				}
			}
			else
			{
				$viewName = $this->getBinding($context, $request);
			}
					
			$rq->endI18nWork();
	    } 
	    catch (Exception  $e)
	    {
	    	$rq->endI18nWork($e);
	    }
	    
		return $viewName;
	}
	
	/**
	 * @param Context $context
	 * @param Request $request
	 * @return string
	 */
	private function getBinding($context, $request)
	{
		$lang = RequestContext::getInstance()->getUILang();

		$binding = $request->getParameter('binding');

		$bindingFile = uixul_lib_BindingObject::getFile($binding);

		if ( ! $bindingFile )
		{
			$e = new FrameworkException('binding_not_found');
			$e->setAttribute('binding', $binding);
			throw $e;
		}

		if ( $request->hasParameter(K::WEBEDIT_MODULE_ACCESSOR) && $request->hasParameter('widgetref') )
		{
			$widgetId   = $request->getParameter('widgetref');
			$moduleName = $request->getParameter(K::WEBEDIT_MODULE_ACCESSOR);
			$cachedBindingFile =
				WEBAPP_HOME . uixul_lib_BindingObject::CACHE_DIR
				. DIRECTORY_SEPARATOR . $binding . '-' . $moduleName . '-' . $widgetId . '-' . $lang . '.xml';
		}
		else
		{
			$cachedBindingFile =
				WEBAPP_HOME . uixul_lib_BindingObject::CACHE_DIR
				. DIRECTORY_SEPARATOR . $binding . '-' . $lang . '.xml';
		}
		$request->setAttribute('cachedFile', $cachedBindingFile);
		$request->setAttribute('bindingFile', $bindingFile);
		return self::FRAMEWORK_BINDING_SUCCESS_VIEW;
	}

	private function getFormBinding($context, $request)
	{
	    $lang = RequestContext::getInstance()->getUILang();

		$binding = $request->getParameter('binding');
		$bindingPathInfo = explode('.', $binding);
		$moduleName = $bindingPathInfo[1];
		$documentName = $bindingPathInfo[3];

		try
		{
			$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
		}
		catch (Exception $e)
		{
			if ($documentName == 'permission')
			{
				$documentModel = null;
			}
			else
			{
				throw $e;
			}
		}

		$cachedBindingFile =
			WEBAPP_HOME . uixul_lib_BindingObject::CACHE_DIR
			. DIRECTORY_SEPARATOR . 'wForm-' . $moduleName . '-' . $documentName . '-' . $lang . '.xml';

		$request->setAttribute('cachedFile', $cachedBindingFile);

		$contentTemplateFile = FileResolver::getInstance()
			->setPackageName('modules_' . $moduleName)
			->setDirectory('forms')
			->getPath($documentName . '_layout.all.all.xul');

		if ( is_null( $contentTemplateFile) )
		{
			throw new TemplateNotFoundException($contentTemplateFile);
		}

		$generator = new uixul_FormBindingService();
		$contents = $generator->generateFromModel($documentModel, $moduleName, $documentName);
		$request->setAttribute('contents', $contents);
		return self::FORM_BINDING_SUCCESS_VIEW;
	}

	public function getRequestMethods()
	{
		return Request::GET;
	}
}
