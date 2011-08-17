<?php

class uixul_lib_cLinkEditorTagReplacer extends f_util_TagReplacer
{
	
	protected function preRun()
	{
		$rc = RequestContext::getInstance();
		$langEntries = array();
		$langEntries[] = '<xul:clistitem value="" label="'. f_Locale::translateUI('&modules.uixul.bo.languages.Not-assigned;') . '" />';
		foreach ($rc->getSupportedLanguages() as $lang)
		{
			$langEntries[] = '<xul:clistitem value="'. $lang . '" label="'. str_replace('"', '&quot;', f_Locale::translateUI('&modules.uixul.bo.languages.'.ucfirst($lang).';')) . '" />';
		}
		
		foreach (f_util_Iso639::getAll($rc->getUILang(), $rc->getSupportedLanguages()) as $code => $label)
		{
			$langEntries[] = '<xul:clistitem value="'. $code . '" label="'. str_replace('"', '&quot;', ucfirst($label)) . '" />';
		}
		$this->setReplacement('LANGS', implode(PHP_EOL, $langEntries));
	}
}