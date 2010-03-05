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
		// FIXME: find a better way to do that...
		$request = $context->getRequest();
		if ($request->hasParameter('wemod'))
		{
			$module = $request->getParameter('wemod');
		}
		else 
		{
			throw new Exception('no module selcted');
		}
		
		$request->setAttribute(K::EFFECTIVE_MODULE_NAME, array($module));
		
		return $result;
	}
}