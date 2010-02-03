<?php
class uixul_GetXmlTreeSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
		$this->setTemplateName('Uixul-Raw', K::XML, 'uixul');

    	$xml = $request->getAttribute('xml', '');
		header("Content-Length: " . strlen($xml));
		$this->setAttribute('contents', $xml);
    }
}