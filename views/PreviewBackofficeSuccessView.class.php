<?php
class uixul_PreviewBackofficeSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

        $this->setAttribute("bindingid", "preview_" . $request->getAttribute("id") . "_" . $request->getAttribute("lang"));

		$this->setTemplateName('Uixul-Preview-Binding', K::XML);
		foreach ($request->getAttributeNames() as $name)
		{
			$this->setAttribute($name, $request->getAttribute($name));
		}

		$rq->endI18nWork();
	}
}