<?php
/**
 * @date Thu Feb 01 11:51:07 CET 2007
 * @author INTbonjF
 */
class uixul_AdminSuccessView extends f_view_BaseView
{
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Uixul-Admin', K::XUL);
		$this->setMimeContentType(K::XUL);
		$rc = RequestContext::getInstance();
		$rc->setUILangFromParameter($request->getParameter('uilang'));
		$_SESSION['uilang']	= $rc->getUILang();
		
		try
		{		
			$this->setAttribute('title', f_Locale::translateUI('&modules.uixul.bo.general.Admin-title;', array('PROJECTNAME' => AG_WEBAPP_NAME)));

			$this->setAttribute('moduleDecks', $this->buildModulesDeck());
			
			$link = LinkHelper::getUIChromeActionLink('uixul', 'GetAdminStylesheets')
				->setQueryParametre('uilang', $rc->getUILang())
				->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
			$this->setAttribute('allStyleUrl', '<?xml-stylesheet href="' . $link->getUrl() . '" type="text/css"?>');
			
			$link = LinkHelper::getUIChromeActionLink('uixul', 'GetAdminJavascripts')
				->setQueryParametre('uilang', $rc->getUILang())
				->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
			$this->setAttribute('scriptlibrary', '<script type="application/x-javascript" src="' . $link->getUrl() . '"/>');
			
			$this->getJsService()->registerScript('modules.uixul.lib.admin');
			$this->setAttribute('scriptInclusion', $this->getJsService()->executeInline(K::XUL));
			
			$this->setAttribute('reloadButtonLabel', f_Locale::translateUI('&modules.uixul.bo.general.ReloadInterfaceSpaced;'));
			$this->setAttribute('reloadLabel', f_Locale::translateUI('&modules.uixul.bo.general.ReloadInterfaceNotification;'));
			$this->setAttribute('dashboardTitle', f_Locale::translateUI('&modules.dashboard.bo.general.Module-nameSpaced;'));
			$this->setAttribute('searchTitle', f_Locale::translateUI('&modules.solrsearch.bo.general.Module-nameSpaced;'));
			$rc->endI18nWork();;
		}
		catch (Exception $e)
		{
			$rc->endI18nWork();
		}
	
	}
	/**
	 * @return Array
	 */
	private function buildModulesDeck()
	{
		$result = array();
		$ms = ModuleService::getInstance();
		$mbs = uixul_ModuleBindingService::getInstance();
		foreach ($ms->getModulesObj() as $moduleObj)
		{
			$moduleName = $moduleObj->getName();
			if ($moduleName == 'dashboard' || !$moduleObj->isVisible())
			{
				continue;
			}
			$iconName = $moduleObj->getIconName();
			$deckAttributes = array('id' => "wmodule_" . $moduleName);
			$deckAttributes['version'] = ($mbs->hasConfigFile($moduleName)) ? 'v3' : 'v2';
			$deckAttributes['title'] = htmlspecialchars(f_Locale::translateUI("&modules.$moduleName.bo.general.Module-nameSpaced;"), 0, "UTF-8");
			$deckAttributes['image'] = MediaHelper::getIcon($iconName);
			$deckAttributes['image-small'] = MediaHelper::getIcon($iconName, MediaHelper::SMALL);
			$result[] = $deckAttributes;
		}
		return $result;
	}
}