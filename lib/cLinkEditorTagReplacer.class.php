<?php

class uixul_lib_cLinkEditorTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$rc = RequestContext::getInstance();
		$langEntries = array();
		$langEntries[] = '<xul:clistitem value="" label="'. LocaleService::getInstance()->trans('m.uixul.bo.languages.not-assigned', array('ucf')) . '" />';
		foreach ($rc->getSupportedLanguages() as $lang)
		{
			$langEntries[] = '<xul:clistitem value="'. $lang . '" label="'. str_replace('"', '&quot;', LocaleService::getInstance()->trans('m.uixul.bo.languages.'.strtolower($lang), array('ucf'))) . '" />';
		}
		
		foreach (f_util_Iso639::getAll($rc->getUILang(), $rc->getSupportedLanguages()) as $code => $label)
		{
			$langEntries[] = '<xul:clistitem value="'. $code . '" label="'. str_replace('"', '&quot;', ucfirst($label)) . '" />';
		}
		$this->setReplacement('LANGS', implode(PHP_EOL, $langEntries));
	}
}