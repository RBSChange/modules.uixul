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
		$ls = LocaleService::getInstance();
		try
		{	
			$txt = $ls->transBO('m.uixul.bo.general.admin-title', array('ucf', 'attr'), array('PROJECTNAME' => AG_WEBAPP_NAME));
			$this->setAttribute('title', $txt);

			$this->setAttribute('moduleDecks', $this->buildModulesDeck());
			
			$link = LinkHelper::getUIChromeActionLink('uixul', 'GetAdminStylesheets')
				->setQueryParameter('uilang', $rc->getUILang())
				->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
			$this->setAttribute('allStyleUrl', '<?xml-stylesheet href="' . $link->getUrl() . '" type="text/css"?>');
			
			$link = LinkHelper::getUIChromeActionLink('uixul', 'GetAdminJavascripts')
				->setQueryParameter('uilang', $rc->getUILang())
				->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
			$this->setAttribute('scriptlibrary', '<script type="application/x-javascript" src="' . $link->getUrl() . '"/>');
			
			$this->getJsService()->registerScript('modules.uixul.lib.admin');
			$this->setAttribute('scriptInclusion', $this->getJsService()->executeInline(K::XUL));
			
			$this->setAttribute('reloadButtonLabel', $ls->transBO('m.uixul.bo.general.reloadInterface', array('ucf', 'space', 'attr')));
			$this->setAttribute('reloadLabel', $ls->transBO('m.uixul.bo.general.reloadinterfacenotification', array('ucf', 'attr')));
			$this->setAttribute('dashboardTitle', $ls->transBO('m.dashboard.bo.general.module-name', array('ucf', 'space', 'attr')));
			$this->setAttribute('searchTitle', $ls->transBO('m.solrsearch.bo.general.module-name', array('ucf', 'space', 'attr')));
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
		$ls = LocaleService::getInstance();
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
			$deckAttributes['title'] = $ls->transBO('m.' . $moduleName . '.bo.general.module-name', array('ucf', 'space', 'attr'));
			$deckAttributes['image'] = MediaHelper::getIcon($iconName);
			$deckAttributes['image-small'] = MediaHelper::getIcon($iconName, MediaHelper::SMALL);
			$result[] = $deckAttributes;
		}
		return $result;
	}
}