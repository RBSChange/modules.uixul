<?php
class uixul_PreviewBackofficeAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
		$id = $this->getDocumentIdFromRequest($request);
		$lang =  RequestContext::getInstance()->getLang();
		$typeObject = ComponentTypeObject::getInstance($id);
		$rq = RequestContext::getInstance();

		if ($typeObject->packageType === ComponentTypeObject::MODULE || ($typeObject->packageType === ComponentTypeObject::FRAMEWORK && $typeObject->componentType === "reference"))
		{
			$ds = $this->getDocumentService();
			$document = $ds->getDocumentInstance($id);

            $rq->beginI18nWork($rq->getUILang());
			$templateComponent = $this->getTemplateForDocument($document);
			$rq->endI18nWork();

			$documentAttribute = array();
			$documentService = ServiceLoader::getServiceByDocumentModelName($document->getDocumentModelName());
			$documentAttribute["extra_attributes"] = $documentService->getPreviewAttributes($document);
			$documentService = null;

			// get the document tags
			$tags = TagService::getInstance()->getTagObjects($document);
			$docTags = array();

			foreach ($tags as $tag)
			{
				$docTags[] = array(
					'value' => $tag->getValue(),
					'label' => f_Locale::translate($tag->getLabel()),
					'icon'  => MediaHelper::getIcon($tag->getIcon(), MediaHelper::COMMAND, null, MediaHelper::LAYOUT_SHADOW)
					);
			}

			$documentAttribute["tags"] = $docTags;

			// get the document attributes (fields)
			$documentAttribute["attributes"] = $this->getStandardAttributes($document, $lang);
			$documentAttribute["status"]   = $document->getPublicationstatus();
			$documentAttribute["creation_date"] = $document->getCreationDate();
			$documentAttribute["modification_date"] = $document->getModificationDate();

			$rq->beginI18nWork($rq->getUILang());

			// the label may be localized, for root folders for example
			if (isset($documentAttribute["attributes"]["label"]))
			{
			    $labelValue = $documentAttribute["attributes"]["label"];
			    $labelValue = str_replace(array('&nbsp;'), array(' '), htmlspecialchars(html_entity_decode(f_Locale::translate($labelValue), ENT_NOQUOTES, 'UTF-8')));
			    $documentAttribute["attributes"]["label"] = $labelValue;
			}

			$model = f_persistentdocument_PersistentDocumentModel::getInstance($typeObject->packageName, $typeObject->componentType);

			// get the system information about the document
			$documentAttribute["id"]       = $id;

			if (method_exists($document, 'getLang'))
			{
				$documentAttribute["lang"] = $document->getLang();
				$request->setAttribute("lang", $documentAttribute["lang"]);
			}

			$documentAttribute["type"]     = $document->getDocumentModelName();
			$documentAttribute["hrtype"]   = $document->getDocumentModelName();
			$documentAttribute["creation_date"] = date_DateFormat::smartFormat($documentAttribute["creation_date"]);
			$documentAttribute["modification_date"] = date_DateFormat::smartFormat($documentAttribute["modification_date"]);
			$templateComponent->setAttribute("document", $documentAttribute);

			// set id, lang, label, icon and contents (revision?)
			$request->setAttribute("id", $id);
			$request->setAttribute("lang", $lang);

			if (isset($documentAttribute["attributes"]["label"]))
			{
			    $request->setAttribute("label", $documentAttribute["attributes"]["label"]);
			}

			$request->setAttribute("icon", MediaHelper::getIcon($model->getIcon(), MediaHelper::SMALL));
			$request->setAttribute("contents", $templateComponent->execute());

			$rq->endI18nWork();
		}

		return View::SUCCESS;
    }
    
    private function getStandardAttributes($document, $lang)
    {
    	$result = array();
    	foreach (DocumentHelper::getPropertiesOf($document, $lang) as $key => $value) 
    	{
    		if (is_string($value))
    		{
    			$result[$key] = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
    		}
    		else
    		{
    			$result[$key] = $value;
    		}
    	}
    	return $result;
    }

    /**
     * @param f_persistentdocument_PersistentDocument $id
     * @return TemplateObject
     */
    private function getTemplateForDocument($document)
    {
		$typeObject = ComponentTypeObject::getInstance($document);

		$templateLoader = Loader::getInstance('template')->setDirectory('templates/preview')->setMimeContentType(K::XUL);
		$templateLoader->setPackageName("modules_" . $typeObject->packageName);

		// Get the template in modules/MODULE/templates/preview/DOCUMENT.all.all.xul
		try
		{
			return $templateLoader->load($typeObject->componentType);
		}
		catch (TemplateNotFoundException $e)
		{
			// The template has not been found, but it is not critical here,
			// since there may be other templates to display document's properties.
		}

		$injectionModel = $document->getPersistentModel()->getSourceInjectionModel();

		// In case of injection for this document, try to get the original template .
		if ( ! is_null($injectionModel) )
		{
			$templateLoader->setPackageName("modules_" . $injectionModel->getModuleName());
			try
			{
				return $templateLoader->load($injectionModel->getDocumentName());
			}
			catch (TemplateNotFoundException $e)
			{ }
		}

		// If no template is found for the document, try to get the one defined
		// for all the documents of a module:
		// modules/MODULE/templates/preview/all.all.all.xul
		try
		{
			$templateLoader->setPackageName("modules_" . $typeObject->packageName);
			return $templateLoader->load('all');
		}
		catch (TemplateNotFoundException $e)
		{ }

		// If no template is found for the module, get the one defined for all the documents.
		// modules/generic/templates/preview/default.all.all.xul
		$templateLoader->setPackageName('modules_uixul');
		return $templateLoader->load('default');
    }

    protected function suffixSecureActionByDocument()
    {
    	return true;
    }
}
