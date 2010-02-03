<?php
class uixul_IndexSuccessView extends PHPView
{
	public function execute()
	{
	    $this->setAttribute('title', f_Locale::translateUI('&modules.uixul.bo.general.Project-name;').' - '.Framework::getCompanyName());

	    if ($this->getContext()->getRequest()->hasParameter('popup') && !$this->getContext()->getRequest()->hasParameter('nopopup'))
	    {
	        $this->setAttribute(
    			'scripts',
    		    JsService::getInstance()
    		       ->registerScript('modules.uixul.lib.jquery')
    		       ->execute()
    		);
    		$this->setAttribute(
               'styles',
               StyleService::getInstance()
    	    	   ->registerStyle('modules.generic.frontoffice')
    	    	   ->registerStyle('modules.users.backoffice')
    	    	   ->execute(K::HTML)
    	    );
    	    $this->setAttribute('welcome', f_Locale::translateUI('&modules.uixul.bo.general.Welcome-to-change;'));
    	    $this->setAttribute('warninglabel', f_Locale::translateUI('&modules.uixul.bo.general.Popup-warning-label;'));
    	    $this->setAttribute('warningdesc', f_Locale::translateUI('&modules.uixul.bo.general.Popup-warning-descLabel;'));
    	    $this->setAttribute('warninglink', f_Locale::translateUI('&modules.uixul.bo.general.Popup-warning-link;'));
    	    $this->setAttribute('warningcheck', f_Locale::translateUI('&modules.uixul.bo.general.Popup-warning-check;'));
    	    $this->setAttribute('logo', MediaHelper::getBackofficeStaticUrl("logoCMS.png"));
	        $this->setAttribute('url', LinkHelper::getUIActionLink('uixul', 'Index')->setQueryParametre('nopopup', 1)->getUrl());
		    $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Uixul-Index-Popup.php';
	    }
	    else
	    {
	        $this->setAttribute('msg', f_Locale::translateUI('&modules.users.bo.general.DisconnectConfirm;'));
		    $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Uixul-Index-Redirection.php';
	    }
		$this->setTemplate($file);

	}
}