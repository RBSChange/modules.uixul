<?php
class uixul_AdminSuccessView extends change_View
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Uixul-Admin', 'xul');
		$this->setMimeContentType('xul');
		$rc = RequestContext::getInstance();
		$rc->setUILangFromParameter($request->getParameter('uilang'));
		$lang = $rc->getUILang();
		
		$lcid = LocaleService::getInstance()->getLCID($lang);
		change_Controller::getInstance()->getStorage()->writeForUser('uiLCID', $lcid);

		$ls = LocaleService::getInstance();

		$txt = $ls->trans('m.uixul.bo.general.admin-title', array('ucf', 'attr'), array('PROJECTNAME' => Framework::getConfigurationValue('general/projectName')));
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
		
		$jss = website_JsService::getInstance();
		$jss->registerScript('modules.uixul.lib.admin');
		$this->setAttribute('scriptInclusion', $jss->executeInline('xul'));
		
		$this->setAttribute('reloadButtonLabel', $ls->trans('m.uixul.bo.general.reloadInterface', array('ucf', 'space', 'attr')));
		$this->setAttribute('reloadLabel', $ls->trans('m.uixul.bo.general.reloadinterfacenotification', array('ucf', 'attr')));
		$this->setAttribute('dashboardTitle', $ls->trans('m.dashboard.bo.general.module-name', array('ucf', 'space', 'attr')));
		$this->setAttribute('searchTitle', $ls->trans('m.solrsearch.bo.general.module-name', array('ucf', 'space', 'attr')));
	}
	
	/**
	 * @return array
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
			$deckAttributes['title'] = $ls->trans('m.' . $moduleName . '.bo.general.module-name', array('ucf', 'space', 'attr'));
			$deckAttributes['image'] = MediaHelper::getIcon($iconName);
			$deckAttributes['image-small'] = MediaHelper::getIcon($iconName, MediaHelper::SMALL);
			$result[] = $deckAttributes;
		}
		return $result;
	}
}