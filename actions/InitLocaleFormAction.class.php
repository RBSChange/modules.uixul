<?php
class uixul_InitLocaleFormAction extends f_action_BaseAction
{
    /**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        $moduleName = $request->getParameter('mod');
        $path = $request->getParameter('path');
        $key = $request->getParameter('key');
        $fullKey = $path.'.'.$key;

        $locales = $this->getPersistentProvider()->getLocalesByPath($fullKey);
	    $locale = $locales[$fullKey];
	    $foundEntity = array();
        foreach ($locale as $lang => $data)
        {
        	$foundEntity[$lang][] = array($data['content'], $data['useredited'] == 1);
        	if ($data['useredited'] == 1)
        	{
        		$foundEntity[$lang][] = array($data['originalcontent'], false);
        	}
        }

		$contents = '<locale>';
		foreach ($foundEntity as $entityLang => $entityVersions)
		{
		    $contents .= '<lang value="' . $entityLang . '">';
		    foreach ($entityVersions as $entityVersion)
		    {
		        if ($entityVersion[1])
		        {
		            $contents .= '<entity overridden="true"><![CDATA[';
		        }
		        else
		        {
		            $contents .= '<entity><![CDATA[';
		        }
		        
		        $cleanEntityVersion = str_replace('&amp;amp;', '&amp;', $entityVersion[0]);
                $cleanEntityVersion = str_replace('&amp;', '&', $cleanEntityVersion);
                $cleanEntityVersion = str_replace('&lt;', '<', $cleanEntityVersion);
                $cleanEntityVersion = str_replace('&gt;', '>', $cleanEntityVersion); 
		        $contents .= $cleanEntityVersion;

		        $contents .= ']]></entity>';
		    }
		    $contents .= '</lang>';
		}
		$contents .= '</locale>';

        $request->setAttribute('contents', $contents);

        return $this->getSuccessView();
    }
}