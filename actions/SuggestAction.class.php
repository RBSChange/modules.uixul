<?php
class uixul_SuggestAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{   	    
	    $q = trim(f_util_StringUtils::strtolower($request->getParameter("q")));
	    
	    $thesaurus = trim($request->getParameter("thesaurus"));
	    
        if ($q && $thesaurus)
        {
            $items = array();
            
            $path = FileResolver::getInstance()
    			->setPackageName('modules_uixul')
    			->setDirectory('lib/thesaurus')
    			->getPath($thesaurus . '.txt');
    		
    		if ($path && is_readable($path))
    		{
    		    $items = file($path);
    		}
            
            foreach ($items as $value) 
            {                
            	if (strpos(f_util_StringUtils::strtolower($value), $q) !== false) 
            	{
            		echo trim($value) . "\n";
            	}
            }            
        }

	    return View::NONE;
	}
}