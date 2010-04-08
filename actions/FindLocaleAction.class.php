<?php
class uixul_FindLocaleAction extends f_action_BaseAction
{

	/**
	 * @param Context $context
	 * @param Request $request
	 */
    public function _execute($context, $request)
    {
        set_time_limit(0);
        
        $how = $request->getParameter('how', 1);
        $what = $request->getParameter('what', 1);
        $match = $request->getParameter('match', 1);
        $value = trim($request->getParameter('value', ''));
        
        if ($how == 1) // 1 : defined
        {
            $pdo = $this->getPersistentProvider()->getDriver();
            $query = 'SELECT id, package, content FROM f_locale WHERE ';
            
            switch ($what)
            {
                case 1: // 1 : key
                    $query .= 'id';
                    break;
                    
                case 2: // 2 : content
                    $query .= 'CONVERT(content USING utf8) COLLATE utf8_general_ci';
                    break;
                    
                default:
                    return $this->getErrorView();
            }
            
            switch ($match)
            {
                case 1:  // 1 : exact
                    $query .= ' = :value';
                    break;
                    
                case 2: // 2: contains
                    $query .= ' LIKE :value';
                    $value = '%' . $value . '%';
                    break;
                    
                case 3: // 3 : begins
                    $query .= ' LIKE :value';
                    $value = $value . '%';
                    break;
                    
                case 4: // 4 : ends
                    $query .= ' LIKE :value';
                    $value = '%' . $value;
                    break;
                    
                default:
                    return $this->getErrorView();
            }            
            
            $query .= " AND lang = :lang ORDER BY package ASC";       
                 
            $stmt = $pdo->prepare($query);
            
            if ($stmt)
            {
                $stmt->bindValue(':value', $value, PDO::PARAM_STR);
                $stmt->bindValue(':lang', RequestContext::getInstance()->getLang(), PDO::PARAM_STR);
                
            	if ($stmt->execute())
            	{
                	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);                
                	$found = '<found how="1">';
                	
                	foreach ($results as $result)
                	{
                	    $found .= sprintf(
                	        '<locale id="%s" package="%s"><![CDATA[%s]]></locale>',
                	        $result['id'],
                	        $result['package'],
                	        $result['content']
                	    );
                	}
                	
                	$found .= '</found>';
                	$request->setAttribute('contents', $found);
            	}
            	else
            	{
            	    return $this->getErrorView();
            	}
            }
            else
        	{
        	    return $this->getErrorView();
        	}
        }
        else if ($how == 2) // 1 : used
        {     
            $value = str_replace('&amp;', '', $value);
            $value = str_replace('&', '', $value);
            $value = str_replace(';', '', $value);
            $value = preg_quote(trim($value));
            
            $query = 'find -L ' . WEBEDIT_HOME . '/framework/ ' . WEBEDIT_HOME . '/modules/ ' . PROJECT_OVERRIDE . '/modules/ \( -iname \*.php -or -iname \*.xml -or -iname \*.xul -or -iname \*.html -or -iname \*.js -or -iname \*.tpl \) -type f -exec grep -Erins "[\'\"]&(amp;)?';
            
            switch ($what)
            {
                case 1: // 1 : key
                    break;
                    
                default:
                    return $this->getErrorView();
            }            
            
            switch ($match)
            {
                case 1:  // 1 : exact
                    $query .= $value;
                    break;
                    
                case 2: // 2: contains
                    $query .= '[^;]*' . $value . '[^;]*';
                    break;
                    
                case 3: // 3 : begins
                    $query .= $value . '[^;]*';
                    break;
                    
                case 4: // 4 : ends
                    $query .= '[^;]*' . $value;
                    break;
                    
                default:
                    return $this->getErrorView();
            }   

            $query .= ';[\'\"]" {} \; -print';
            
            $results = array();
                                    
            exec($query, $results);
            
            $found = '<found how="2">';       
            $foundBuffer = array();
            
            foreach ($results as $result)
            {
                if (preg_match('/^(\d+):(.*)/', $result, $resultMatch))
                {
                    $foundBuffer[] = array('line' => $resultMatch[1], 'content' => trim($resultMatch[2]));
                }
                else
                {
                    $package = trim(str_replace(WEBEDIT_HOME . '/', '', $result));
                    foreach ($foundBuffer as $foundBufferEntry)
                    {
                        $found .= sprintf(
                	        '<locale id="%s" package="%s"><![CDATA[%s]]></locale>',
                	        $foundBufferEntry['line'],
                	        $package,
                	        $foundBufferEntry['content']
                	    );     
                    }   
                    $foundBuffer = array();
                }                
            }
            
            $found .= '</found>';
        	$request->setAttribute('contents', $found);
        }
        else
        {
            return $this->getErrorView();
        }
        
		return $this->getSuccessView();
	}
}