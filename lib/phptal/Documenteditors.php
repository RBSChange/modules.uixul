<?php

class PHPTAL_Php_Attribute_CHANGE_Documenteditors extends PHPTAL_Php_Attribute
{
	/**
	 * Called before element printing.
	 */
	public function before(PHPTAL_Php_CodeWriter $codewriter)
	{
		$expressions = $codewriter->splitExpression($this->expression);
		$module = 'null';
		// foreach attribute
		foreach ($expressions as $exp)
		{
			list($attribute, $value) = $this->parseSetExpression($exp);
			switch ($attribute)
			{
				case 'module':
					$module = '"'.$value.'"';
					break;
			}
		}

		$codewriter->doSetVar('$module', $module);
		$codewriter->doEchoRaw('PHPTAL_Php_Attribute_CHANGE_Documenteditors::render($module)');
	}

	/**
	 * Called after element printing.
	 */
	public function after(PHPTAL_Php_CodeWriter $codewriter)
	{
	}
	
	/**
	 * @param string $module
	 * @return string
	 */
	public static function render($module)
	{
		return uixul_DocumentEditorService::getInstance()->getDocumentEditorsForModule($module);
	}
}

