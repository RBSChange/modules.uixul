<?php
class uixul_GetDocumentListAction extends f_action_BaseAction
{
	protected $browsableComponents = array('modules_generic/folder');

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$listParentId = array();

		// Retrieve request data
		$moduleName = $this->getModuleName($request);

		$relationName = $request->getParameter('rn', null);

		if ($request->getParameter('rt', 't') == 't') // tree
		{
			$this->retrieveDocumentsInTree($request);
		}
		else
		{
			$this->retrieveChildDocuments($request, $relationName);
		}

		return View::SUCCESS;
	}


	/**
	 * @param Request $request
	 * @param string $relationName
	 */
	private function retrieveChildDocuments($request, $relationName)
	{
		$parent = $this->getDocumentInstanceFromRequest($request);
		$methodName = 'get'.ucfirst($relationName).'Array';
		if (f_util_ClassUtils::methodExists($parent, $methodName))
		{
			$request->setAttribute('documents', $parent->{$methodName}());
		}
	}


	/**
	 * @param Request $request
	 */
	private function retrieveDocumentsInTree($request)
	{
		$moduleName = $this->getModuleName($request);
		$componentTypes = $request->getParameter(K::COMPONENT_ACCESSOR);
		$icon = $request->getParameter('icons', null);
		if ( ! empty($componentTypes) )
		{
			if ( ! is_array($componentTypes) )
			{
				$componentTypes = array($componentTypes);
			}
			foreach ($componentTypes as $type)
			{
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type);
				$baseClass[] = $model->getModuleName().'_persistentdocument_'.$model->getDocumentName();
			}
		}
		else
		{
			$baseClass = null;
		}

		$moduleRootFolderId = ModuleService::getInstance()->getRootFolderId($moduleName);

		if ( !$request->hasParameter(K::COMPONENT_ID_ACCESSOR)
			|| (
				!is_numeric($request->getParameter(K::COMPONENT_ID_ACCESSOR))
				&& !is_array($request->getParameter(K::COMPONENT_ID_ACCESSOR))
				)
			)
		{
			$listParentId[] = $moduleRootFolderId;
			// intcours - 18/06/2006 : if no parentid is given, all the tree will be sent :
			$deep = true;
		}
		else
		{
			$list = $request->getParameter(K::COMPONENT_ID_ACCESSOR);

			if( ! is_array($list))
			{
				$list = array($list);
			}

			// For all parent id, check if it's a numeric. If doesn't numeric make a line in log and
			// delete the value of list
			foreach($list as $parentId)
			{
				if ( ! is_numeric($parentId) )
				{
					$listParentId[] = $moduleRootFolderId;
					if (Framework::isDebugEnabled())
					{
						Framework::debug("One parameter that it's passed for load sub list, isn't a numeric ! Param is : ".$parentId);
					}
				}
				else
				{
					$listParentId[] = $parentId;
				}
			}

			// intcours - 18/06/2006 : if a parentid is given, only the first level will be sent :
		    $deep = false;
		}

		// here we can specify which type of browsable components
		if ( $request->hasParameter("browsable") )
		{
			$deep = true;
			$this->browsableComponents[0] = $request->getParameter("browsable");
		}

		if ($request->hasParameter('includeFolder')
		&& (intval($request->getParameter('includeFolder')) == 1
		|| $request->getParameter('includeFolder') == "true"))
		{
			$includeFolder = true;
		}
		else
		{
		    $includeFolder = false;
		}

		$documents = array();
		foreach($listParentId as $parentId)
		{
			$documents = array_merge($this->getDocumentList($parentId, $baseClass, $deep, 0, $includeFolder, $icon), $documents);
		}

		$request->setAttribute('deep', $deep);

		$request->setAttribute('documents', $documents);
	}

	protected function getDocumentList($parentId, $baseClass, $deep, $level = 0, $includeFolder = false, $icon = null)
	{
		$parent = $this->getDocumentService()->getDocumentInstance($parentId);
	    $documents = $this->getDocumentService()->getChildrenOf($parent);

	    $leveledList = array();
	    foreach ($documents as $document)
	    {
	    	if (!is_null($baseClass))
	    	{
		    	$classIndex = -1;
		    	for ($i=0 ; $i<count($baseClass) ; $i++)
		    	{
		    		if (is_a($document, $baseClass[$i]))
		    		{
				    	$classIndex = $i;
		    			break;
		    		}
		    	}
	    	}
	    	else
	    	{
	    		$classIndex = 0;
	    	}
	    	if ($classIndex != -1
	    	&& ($document->getPersistentModel()->useCorrection() == false || !$document->getCorrectionofid())
	        && ($includeFolder || ($document->getDocumentModelName() != 'modules_generic/folder'))
	        && (substr($document->getDocumentModelName(), -12) != '/preferences'))
	        {
	            if ($document->getDocumentModelName() == 'modules_generic/folder')
	            {
	                $isFolder = true;
	            }
	            else
	            {
	                $isFolder = false;
	            }

    	        $info = array(
    	            'document' => $document,
    	            'parent' => $parentId,
    	            'level' => $level,
    	            'isFolder' => $isFolder,
    	        	);
    	       	if (is_null($icon) || (is_array($icon) && !isset($icon[$classIndex])))
    	        {
    	        	$info['icon'] = $document->getPersistentModel()->getIcon();
    	        }
    	        else if (is_array($icon))
    	        {
   	        		$info['icon'] = $icon[$classIndex];
    	        }
    	        else if (is_string($icon))
    	        {
    	        	$info['icon'] = $icon;
    	        }
    	        $leveledList[] = $info;
	        }
	    }
	    if ($deep === true)
	    {
	        $subFolders = $this->getDocumentService()->getChildrenOf($parent);
            foreach ($subFolders as $subFolder)
            {
    	        $leveledList = array_merge(
    	            $leveledList,
    	            $this->getDocumentList(
    	                $subFolder,
    	                $componentType,
    	                $deep,
    	                $level + 1
    	            )
    	        );
            }
	    }
	    return $leveledList;
	}
}
