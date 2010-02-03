<?php
// +---------------------------------------------------------------------------+
// | This file is part of the WebEdit4 package.                                |
// | Copyright (c) 2005 RBS.                                                   |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


/**
 * @date    2006-03-24
 * @package modules_generic
 * @author  INTbonjF
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