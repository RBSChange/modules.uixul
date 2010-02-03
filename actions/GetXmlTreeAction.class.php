<?php
class uixul_GetXmlTreeAction extends f_action_BaseAction
{
	
	/*
	&module=uixul
	&action=GetXmlTree
	
	&root=10011											(facultatif)
	&wemod=users										WEBEDIT_MODULE_ACCESSOR
	&cmpref=10011										COMPONENT_ID_ACCESSOR
	&parser=XmlList										PARSER_ACCESSOR (facultatif -> Xml)
	&lang=fr											COMPONENT_LANG_ACCESSOR
	&treeType=wmultilist								TREE_TYPE
	&treeId=modules_jardin_widget_rscTree_users_0List	TREE_ID

	

	&lnkcmp[0]=modules_generic%2Frootfolder				LINKED_COMPONENT_ACCESSOR (Array)
	&lnkcmp[1]=modules_generic%2Ffolder
	&lnkcmp[2]=modules_users%2Fbackendgroup
	
	&order=label%2Fasc									TREE_ORDER (Facultatif)
	&order=publicationstatus%2Fdesc
	
	&treeFilter=ee										TREE_FILTER (Facultatif)
	&treeFilter=author%3Aww	

	&viewcols=label,field1,field2							
	
	*/

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
		$moduleName = $this->getModuleName($request);

		$parserName = $request->getParameter(K::PARSER_ACCESSOR, 'Xml');

		$treeId = $request->getParameter(K::TREE_ID);

		$treeType = $request->getParameter(K::TREE_TYPE);

		$componentId = $request->getParameter(K::COMPONENT_ID_ACCESSOR);

		$rootId = ModuleService::getInstance()->getRootFolderId($moduleName);

		$offset = intval($request->getParameter(K::TREE_OFFSET, 0));

		$order = $request->getParameter(K::TREE_ORDER);

		$filter = $request->getParameter(K::TREE_FILTER);
		
		// instanciate the tree parser of the module
		$parser = tree_parser_TreeParser::getInstance($parserName, $moduleName, $rootId, $treeId, $treeType);
		
		if ($request->hasParameter(K::LINKED_COMPONENT_ACCESSOR))
		{
			$parser->setChildrenTypes($request->getParameter(K::LINKED_COMPONENT_ACCESSOR));
		}
		
    	if ($request->hasParameter('viewcols'))
		{
			$parser->setViewCols($request->getParameter('viewcols'));
		}
		
    	if ($request->hasParameter('pathToFollow'))
		{
			$parser->setSearchedIds(explode('/', $request->getParameter('pathToFollow')));
		}
		
		$xml = $parser->getTree($componentId, $offset, $order, $filter)->saveXML();

		$request->setAttribute('xml', $xml);
    	return View::SUCCESS;
    }
}
