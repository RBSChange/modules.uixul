<?php

class uixul_TestNewFormSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Uixul-TestNewForm1', K::XUL);	
		$this->setMimeContentType(K::XUL);
		
		$link = LinkHelper::getUIChromeActionLink('uixul', 'GetAdminStylesheets')
			->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
		$this->setAttribute('allStyleUrl', '<?xml-stylesheet href="' . $link->getUrl() . '" type="text/css"?>');
				
		$this->getJsService()->registerScript('modules.uixul.lib.wToolkit');
		
		$this->setAttribute('scriptInclusion', $this->getJsService()->executeInline(K::XUL));		
	}
}
