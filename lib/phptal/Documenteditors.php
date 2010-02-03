<?php

class PHPTAL_Php_Attribute_CHANGE_documenteditors extends PHPTAL_Php_Attribute
{
	public function start()
	{
		$expressions = $this->tag->generator->splitExpression($this->expression);
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

		$this->tag->generator->doSetVar('$module', $module);
		$this->tag->generator->doEcho('PHPTAL_Php_Attribute_CHANGE_documenteditors::render($module)');
	}

	public function end()
	{
	}
	
	/**
	 * @param String $module
	 * @return String
	 */
	public static function render($module)
	{
		return uixul_DocumentEditorService::getInstance()->getDocumentEditorsForModule($module);
	}
}

