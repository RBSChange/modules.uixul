<?php
/**
 * uixul_GetDocumentToSortAction
 * @package modules.uixul.actions
 */
class uixul_GetDocumentsToSortAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$parent = $this->getDocumentInstanceFromRequest($request);
		$documents = array();
		if ($request->hasParameter('relationName'))
		{
			$methodName = 'get' . ucfirst($request->getParameter('relationName')) . 'Array';
			if (f_util_ClassUtils::methodExists($parent, $methodName))
			{
				$documents = $parent->{$methodName}();
			}
		}
		else
		{
			$children = $parent->getDocumentService()->getChildrenOf($parent);
			if (f_util_ArrayUtils::isNotEmpty($children))
			{
				$this->initModelCheck($request);
				foreach ($children as $document)
				{
					if ($this->checkModel($document))
					{
						$documents[] = $document;
					}
				}
			}
		}
		
		if (count($documents) < 2)
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.uixul.bo.orderChildrenPanel.Cannot-order-children;'));
		}
		
		$nodes = array();
		foreach ($documents as $document)
		{
			$nodes[] = $this->getInfosForDocument($document);
		}
		$result['nodes'] = $nodes;

		return $this->sendJSON($result);
	}
	
	/**
	 * @var boolean
	 */
	private $filterByModel = false;
	
	/**
	 * @var string[]
	 */
	private $allowesClasses = array();
	
	/**
	 * @param change_Request $request
	 */
	private function initModelCheck($request)
	{
		$modelNames = $request->getParameter('modelNames');
		if (f_util_ArrayUtils::isNotEmpty($modelNames))
		{
			$this->filterByModel = true;
			foreach ($modelNames as $modelName)
			{
				list($packageName, $documentName) = explode('/', $modelName);
				list(, $moduleName) = explode('_', $packageName);
				$this->allowedClasses[] = $moduleName . '_persistentdocument_' . $documentName;
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	private function checkModel($document)
	{
		if ($this->filterByModel)
		{
			foreach ($this->allowedClasses as $class)
			{
				if (is_a($document, $class))
				{
					return true;
					break;
				}
			}
			return false;
		}
		return true;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array
	 */
	protected function getInfosForDocument($document)
	{
		return array(
			'id' => $document->getId(),
			'label' => $document->getTreeNodeLabel(),
			'icon' => MediaHelper::getIcon($document->getPersistentModel()->getIcon(), MediaHelper::SMALL)
		);
	}
}