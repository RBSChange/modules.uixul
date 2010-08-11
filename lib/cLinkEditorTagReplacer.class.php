<?php

class uixul_lib_cLinkEditorTagReplacer extends f_util_TagReplacer
{
	
	protected function preRun()
	{
		$rc = RequestContext::getInstance();
		$langEntries = array();
		$langEntries[] = '<xul:clistitem value="" label="'. f_Locale::translateUI('&modules.uixul.bo.languages.unknown;') . '" />';
		foreach ($rc->getSupportedLanguages() as $lang)
		{
			$langEntries[] = '<xul:clistitem value="'. $lang . '" label="'. f_Locale::translateUI('&modules.uixul.bo.languages.'.ucfirst($lang).';') . '" />';
		}
		$this->setReplacement('LANGS', implode(K::CRLF, $langEntries));
	}
	
	
}