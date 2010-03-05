<?php
class uixul_EditLocaleAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        $request->setAttribute('moduleName', $this->getModuleName($request));

		return View::INPUT;
	}
	
	/**
	 * Initialize this action.
	 *
	 * @param Context The current application context.
	 * @return bool true, if initialization completes successfully, otherwise false.
	 */
	public function initialize($context)
	{
		$result = parent::initialize($context);
		
		// Force to ckeck the permission on the good module instead of uixul.
		$request = $context->getRequest();
		if ($request->hasParameter('wemod'))
		{
			$module = $request->getParameter('wemod');
			$array = $request->getAttribute(K::EFFECTIVE_MODULE_NAME);
			array_pop($array);
			array_push($array, $module);
			
			$request->setAttribute(K::EFFECTIVE_MODULE_NAME, $array);
		}
		else 
		{
			throw new Exception('no module selected');
		}
		
		return $result;
	}
}