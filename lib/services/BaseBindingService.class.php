<?php
class uixul_BaseBindingService
{

	const NS_TAL    = 'http://phptal.motion-twin.com/tal';
	const NS_I18N   = 'http://phptal.motion-twin.com/i18n';
	const NS_XUL    = 'http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul';
	const NS_XBL    = 'http://www.mozilla.org/xbl';
	const NS_CHANGE = 'http://www.rbs-change.eu/change-4.2/taglib/phptal';


    protected $XPathObject = null;


	protected function XPathQuery($domDoc, $query, $context = null)
	{
		if (is_null($this->XPathObject))
		{
			$this->XPathObject = new DOMXPath($domDoc);
			$this->XPathObject->registerNamespace('xul', self::NS_XUL);
			$this->XPathObject->registerNamespace('xbl', self::NS_XBL);
		}
		if ($context instanceof DOMNode)
		{
			return $this->XPathObject->query($query, $context);
		}
		else
		{
			return $this->XPathObject->query($query);
		}
	}
}