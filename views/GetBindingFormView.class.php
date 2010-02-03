<?php
class uixul_GetBindingFormView extends f_view_BaseView
{
	protected function sendHttpHeaders()
	{
	}
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Raw', K::XML, 'uixul');

		$moduleName = $request->getAttribute('moduleName');

		$formName   = $request->getAttribute('formName');

		$lang = RequestContext::getInstance()->getUILang();

		$contents = $request->getAttribute('contents');
		/*
		try
		{
            $className = "" . $moduleName . "_lib_Form" . ucfirst($formName) . "TagReplacer";
			Loader::getInstance('class')->load($className);
		    $tagReplacer = new $className();
		}
		catch (ClassNotFoundException $e)
		{
			$tagReplacer = new f_util_TagReplacer();
		}
		$contents = $tagReplacer->run($contents, true);

		$template = Loader::getInstance('template')->setPackageName('modules_uixul')->setMimeContentType(K::XML)->load('Uixul-Form-Binding');
		$template->setAttribute('contents', $contents);
		$template->setAttribute('bindingId', 'wForm-' . $moduleName . '-' . $formName);
		$template->setAttribute('extends', uixul_lib_BindingObject::getUrl('form.wForm', true) . '#wForm');
		$template->setAttribute('generationDate', sprintf("<!-- generation time: %s -->", time()));

		$contents = $template->execute();
		*/

		$cachedBindingFile = $request->getAttribute('cachedFile');

		f_util_FileUtils::mkdir(dirname($cachedBindingFile));

		$fileHandle = fopen($cachedBindingFile, 'w');
		if ( ! $fileHandle )
		{
			$e = new FrameworkException("file_is_not_writable");
			$e->setAttribute('file', $cachedBindingFile);
			$rq->endI18nWork($e);
		}

		fwrite($fileHandle, $contents);
		fclose($fileHandle);

		$this->setAttribute('contents', $contents);

		$rq->endI18nWork();
	}
}