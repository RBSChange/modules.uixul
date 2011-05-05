<?php
/**
 * @package modules.uixul
 */
class uixul_lib_Action
{
	public $name       = null;
	public $label      = null;
	public $icon       = null;
	public $parameters = array();
	public $body       = null;
	public $localized  = false;
	public $global     = false;
	public $checkDisplay = 'return true;';
	public $hasSeparator = false;
	
	public function debug()
	{
		printf(
			"<strong>%s</strong><br />%s<br />%s<br />%s<br />%s<hr />\n",
			$this->name, $this->label, $this->icon, join(", ", $this->parameters), nl2br(str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $this->body))
		);
	}
}