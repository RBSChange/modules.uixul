<?php
class tree_parser_LocalepackageTreeParser extends tree_parser_XmlTreeParser
{
    public $moduleName = '';
    
    public $handledLangs = array();

    public $path = '';

	/**
     * Main method used to retrieve the tree data.
     *
     * @param integer $documentId ID of the root component from which the parsing
	 * should begin.
     * @param integer $offset Pagination index.
     * @param string $order User-defined ordering data.
     * @param string $filter User-defined filtering data.
     * @return DOMDocument XML tree data
     */
	public function getTree($documentId = null, $offset = 0, $order = null, $filter = null)
	{
		try
		{
			$this->moduleName = HttpController::getGlobalContext()->getRequest()->getParameter('source');
			$this->handledLangs = explode('/', HttpController::getGlobalContext()->getRequest()->getParameter('handeled-languages'));
	    
			if ($documentId && $documentId != 'locale')
			{
                $this->path = $documentId;
			}
			else if ($this->moduleName == 'framework')
			{
				$this->path = $this->moduleName;
			}
			else 
			{
				$this->path = 'modules.'.$this->moduleName;
			}

			// User-defined offset (pagination) :
			if ($offset)
			{
				$this->setOffset($offset);
			}

			// User-defined order (overwrite the predefined one, if any) :
			if ($order)
			{
				$this->setOrder($order);
			}

			// User-defined filter (overwrite the predefined one, if any) :
			if ($filter)
			{
				$this->setFilter($filter);
			}

			$this->xmlDoc = $this->createXmldocRootElement();
			$currentNode = $this->getXmlDocRootNode();
			if ($this->getTreeType() == self::TYPE_TREE)
			{
				$this->explorePath($this->path, $currentNode);
			}
			else
			{
				$this->explorePackage($this->path, $currentNode);
			}
		}
		catch (Exception $e)
		{
		    Framework::exception($e);
			return $this->getXmlErrorMessage($e->getMessage());
		}
		
		return $this->xmlDoc;
	}

	private function getLocaleGroups($keys, $path)
	{
		$level = substr_count($path, '.') + 1;
	   	$groups = array();
		foreach ($keys as $key)
		{
			$key = substr($key, 0, strrpos($key, '.'));
			$groupNames = array_slice(explode('.', $key), $level);
			$this->addGroup($groups, $groupNames);
		}
		return $groups;
	}
	
	private function addGroup(&$parent, $groupNames)
	{
		if (count($groupNames) > 0)
		{
			$groupName = array_shift($groupNames);
			if (!isset($parent[$groupName]))
			{
				$parent[$groupName] = array();
			}
			$this->addGroup($parent[$groupName], $groupNames);
		}
	}
	
	public function explorePath($path, $currentNode, $level = 1, $groups = null)
	{
		if ($groups === null)
		{
		    $locales = $this->getPersitentProvider()->getLocalesByPath($path);
		    $groups = $this->getLocaleGroups(array_keys($locales), $path);
			$newNode = $this->createNode($path, $path, $level, 'root');
			$currentNode->appendChild($newNode);
			$currentNode = $newNode;
		}
		
		ksort($groups);
		foreach ($groups as $group => $subGroups)
		{
			$thisPath = $path . '.' . $group;
			$newNode = $this->createNode($thisPath, $group, $level + 1, 'folder');
			$currentNode->appendChild($newNode);
			if (count($subGroups) > 0)
			{
				$this->explorePath($thisPath, $newNode, $level + 1, $subGroups);
			}
		}
	    return;
	}

	private function getLocalesForPath($path)
	{
		$locales = $this->getPersitentProvider()->getLocalesByPath($path);
	    $level = substr_count($path, '.') + 1;
	   	$values = array();
	   	foreach ($locales as $key => $locale)
		{
			if (substr_count($key, '.') == $level)
			{
				$values[array_pop(explode('.', $key))] = $locale;
			}
		}
		ksort($values);
		return $values;
	}

	public function explorePackage($path, $currentNode, $level = 1)
	{
	    $newNode = $this->createNode($path, $path, $level, 'root');
		$currentNode->appendChild($newNode);
		$currentNode = $newNode;
		
		$locales = $this->getLocalesForPath($path);
		$entities = array();
		$cleanEntities = array();
		$usereditedEntities = array();
	    $defaultLang = RequestContext::getInstance()->getLang();
	    
	    foreach ($locales as $key => $locale)
	    {
	    	$thisLocale = $locale[$defaultLang];
	    	foreach ($this->handledLangs as $lang)
	    	{
		    	if (isset($locale[$lang]) &&  $locale[$lang]['useredited'] == 1)
		    	{
		    		$usereditedEntities[$key] = true;
		    	}
	    	}	    	
	    	$entities[$key]['original'][$defaultLang] = $thisLocale['content'];
	    	$cleanEntities[$key] = $thisLocale['content'];
	    }

	    // Children are filtered :
		if ($this->hasFiltering())
		{
		    $unfilteredEntities = $cleanEntities;
            $cleanEntities = array();
			foreach ($unfilteredEntities as $key => $value)
			{
			    $filter = $this->getFilter();

			    if (isset($filter[self::FILTER_VALUE]) && isset($filter[self::FILTER_BY]))
			    {
		            if ((trim(strtolower($filter[self::FILTER_BY])) == 'key')
		            && (strpos($key, $filter[self::FILTER_VALUE]) !== false))
			        {
			            $cleanEntities[$key] = $value;
			        }
			        else if ((trim(strtolower($filter[self::FILTER_BY])) == 'val')
			        && (strpos($value, $filter[self::FILTER_VALUE]) !== false))
			        {
			            $cleanEntities[$key] = $value;
			        }
			        else if ((trim(strtolower($filter[self::FILTER_BY])) == self::FILTER_BY_LABEL)
			        && ((strpos($value, $filter[self::FILTER_VALUE]) !== false)
                    || (strpos($key, $filter[self::FILTER_VALUE]) !== false)))
                    {
                        $cleanEntities[$key] = $value;
                    }
			    }
			}
		}

		// Children are ordered :
		if ($this->hasOrdering())
		{
		    if ($this->getOrderColumn() == 'label')
            {
                if ($this->getOrderDirection() == self::ORDER_DESC)
                {
                    krsort($cleanEntities, SORT_STRING);
                }
                else if ($this->getOrderDirection() == self::ORDER_ASC)
                {
			        ksort($cleanEntities, SORT_STRING);
                }
            }
            else if ($this->getOrderColumn() == 'value')
            {
                if ($this->getOrderDirection() == self::ORDER_DESC)
                {
                    natcasesort($cleanEntities);
                    $cleanEntities = array_reverse($cleanEntities, true);
                }
                else if ($this->getOrderDirection() == self::ORDER_ASC)
                {
                    natcasesort($cleanEntities);
                }
            }
		}

		$count = 0;

		if (count($cleanEntities) > $this->getLength())
		{
		    $currentNode->setAttribute(self::ATTRIBUTE_PAGE_TOTAL , ceil(count($cleanEntities) / $this->getLength()));

		    $cleanEntities = array_slice($cleanEntities, $this->getOffset() * $this->getLength());
		}

        foreach ($cleanEntities as $key => $value)
        {
            $newNode = $this->createEntityNode($path, $key, $value, false, isset($usereditedEntities[$key]) && $usereditedEntities[$key]);

	        $currentNode->appendChild($newNode);

	        $count++;

            if ($this->getAvailableLength($level+1) && ($count >= $this->getAvailableLength($level+1)))
			{
				$currentNode->setAttribute(self::ATTRIBUTE_PAGE_NEXT, $this->getOffset() + 1);
				break;
			}
        }
	}


	/**
	 * @param String $path
	 * @param String $key
	 * @param String $value
	 * @param Boolean $overridden
	 * @return XMLElement
	 */
	public function createEntityNode($path, $key, $value, $overridden = false, $useredited = false)
	{
		$currentNode = $this->xmlDoc->createElement(self::DOCUMENT_NODE);
		$currentNode->setAttribute(self::ATTRIBUTE_ID, $key);
		$currentNode->setAttribute(self::ATTRIBUTE_CONTEXT_LANG_AVAILABLE, '1');
		$currentNode->setAttribute(self::ATTRIBUTE_PARENT_ID, $path);
        $currentNode->setAttribute(self::ATTRIBUTE_TYPE, 'editlocale_entity');
		$nodeLabel = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $key);
		$nodeLabel->setAttribute(self::ATTRIBUTE_NAME, self::LABEL_ATTRIBUTE);
		$currentNode->appendChild($nodeLabel);

		$nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $value);
		$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'value');
		$currentNode->appendChild($nodeAttribute);

		$fullKey = '&amp;' . $path . '.' . $key . ';';
		$keyAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, str_replace('..', '.', $fullKey));
		$keyAttribute->setAttribute(self::ATTRIBUTE_NAME, 'key');
		$currentNode->appendChild($keyAttribute);

		$moduleAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $this->moduleName);
		$moduleAttribute->setAttribute(self::ATTRIBUTE_NAME, 'module');
		$currentNode->appendChild($moduleAttribute);

		$currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, f_permission_PermissionService::ALL_PERMISSIONS);

		if ($overridden)
        {
            $nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, 'overridden');
			$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'overridden');
			$currentNode->appendChild($nodeAttribute);
		}

		if ($useredited)
        {
            $nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, 'useredited');
			$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'useredited');
			$currentNode->appendChild($nodeAttribute);
        }

		return $currentNode;
	}


	/**
	 * @param String $path
	 * @param Integer $level
	 * @param String $type
	 * @param Boolean $overridden
	 * @return XMLElement
	 */
	public function createNode($path, $label, $level, $type, $overridden = false)
	{
		$currentPath = $path;
		$currentNode = $this->xmlDoc->createElement(self::DOCUMENT_NODE);
		$currentNode->setAttribute(self::ATTRIBUTE_ID, $path);
		$currentNode->setAttribute(self::ATTRIBUTE_CONTEXT_LANG_AVAILABLE, '1');
        $currentNode->setAttribute(self::ATTRIBUTE_TYPE, 'editlocale_' . $type);
        
        $nodeLabel = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $label);
		$nodeLabel->setAttribute(self::ATTRIBUTE_NAME, self::LABEL_ATTRIBUTE);
		$currentNode->appendChild($nodeLabel);

		if ($overridden)
        {
            $nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, 'overridden');
			$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'overridden');
			$currentNode->appendChild($nodeAttribute);
        }

        if ($this->getTreeType() == self::TYPE_TREE && ($level > 1))
        {
            $nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $path);
    		$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'key');
    		$currentNode->appendChild($nodeAttribute);

    		$nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $this->moduleName);
    		$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'module');
    		$currentNode->appendChild($nodeAttribute);

    		$nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE, $path);
    		$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, 'path');
    		$currentNode->appendChild($nodeAttribute);
        }

		return $currentNode;
	}


	/**
     * Normalize the given value for XML tree data.
     *
     * @param string $value
     * @return string
     */
	protected function normalizeValue($value)
	{
		$value = parent::normalizeValue($value);

		if (strtolower(substr($value, strrpos($value, '.') + 1)) == 'xml')
        {
            $value = substr($value, 0, strrpos($value, '.'));
        }

        return $value;
	}
}

class uixul_locale_LocalepackageTreeParser extends tree_parser_LocalepackageTreeParser
{
	
}