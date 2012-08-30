<?php
/**
 * @package modules.uixul
 * @method uixul_ModuleBindingService getInstance()
 */
class uixul_ModuleBindingService extends change_BaseService
{
	public function addImportInPerspective($forModuleName, $fromModuleName, $configFileName)
	{
		$destPath = f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config', 'perspective.xml');
		$result = array('action' => 'ignore', 'path' => $destPath);
		
		$path = change_FileResolver::getNewInstance()->getPath('modules', $fromModuleName, 'config', $configFileName .'.xml');
		if ($path === null)
		{
			throw new Exception(__METHOD__ . ' file ' . $fromModuleName . '/config/' . $configFileName . '.xml not found');
		}
		
		if (!file_exists($destPath))
		{
			$document = f_util_DOMUtils::fromString('<perspective />');
			$result['action'] = 'create';
		}
		else
		{
			$document = f_util_DOMUtils::fromPath($destPath);
		}
		
		$xquery = 'import[@modulename="'.$fromModuleName.'" and @configfilename="'.$configFileName.'"]';	
		$importNode = $document->findUnique($xquery, $document->documentElement);
		if ($importNode === null)
		{
			f_util_FileUtils::mkdir(f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config'));
			$importNode = $document->documentElement->appendChild($document->createElement('import'));	
			$importNode->setAttribute('modulename', $fromModuleName);
			$importNode->setAttribute('configfilename', $configFileName);
			f_util_DOMUtils::save($document, $destPath);
			if ($result['action'] == 'ignore') {$result['action'] = 'update';}
		}
		return $result;
	}
	
	public function addImportInActions($forModuleName, $fromModuleName, $configFileName)
	{
		$destPath = f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config', 'actions.xml');
		$result = array('action' => 'ignore', 'path' => $destPath);
		
		$path = change_FileResolver::getNewInstance()->getPath('modules', $fromModuleName, 'config', $configFileName .'.xml');
		if ($path === null)
		{
			throw new Exception(__METHOD__ . ' file ' . $fromModuleName . '/config/' . $configFileName . '.xml not found');
		}
		
		if (!file_exists($destPath))
		{
			$document = f_util_DOMUtils::fromString('<actions />');
			$result['action'] = 'create';
		}
		else
		{
			$document = f_util_DOMUtils::fromPath($destPath);
		}
		
		$xquery = 'import[@modulename="'.$fromModuleName.'" and @configfilename="'.$configFileName.'"]';
		$importNode = $document->findUnique($xquery, $document->documentElement);
		if ($importNode === null)
		{
			f_util_FileUtils::mkdir(f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config'));
			$importNode = $document->documentElement->appendChild($document->createElement('import'));	
			$importNode->setAttribute('modulename', $fromModuleName);
			$importNode->setAttribute('configfilename', $configFileName);
			f_util_DOMUtils::save($document, $destPath);
			if ($result['action'] == 'ignore') {$result['action'] = 'update';}
		}
		return $result;
	}
	
	/**
	 * After this method, uixul_DocumentEditorService::getInstance()->compileEditorsConfig()
	 * should be executed if the 'action' key in result contains 'create'.
	 * 
	 * @param string $forModuleName
	 * @param string $modelName
	 * @return array
	 */
	public function addImportForm($forModuleName, $modelName)
	{
		list($package, $documentName) = explode('/', $modelName);
		$destPath = f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'forms', 'editor', $documentName, 'panels.xml');
		$result = array('action' => 'ignore', 'path' => $destPath);
		
		if (!file_exists($destPath))
		{
			list(, $moduleName) = explode('_', $package);
			$document = f_util_DOMUtils::fromString('<panels module="' . $moduleName . '" />');
			f_util_FileUtils::mkdir(dirname($destPath));
			f_util_DOMUtils::save($document, $destPath);
			$result['action'] = 'create';
		}
		return $result;
	}
	
	/**
	 * @param string $forModuleName
	 * @param string $documentName
	 * @param string $panelName
	 * @param string $overridedBy [[moduleName.]documentName.]panelName
	 * @return array
	 */
	public function overridePanel($forModuleName, $documentName, $panelName, $overridedBy)
	{
		$destPath = f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'forms', 'editor', $documentName, $panelName . '.xml');
		$result = array('action' => 'ignore', 'path' => $destPath);
		
		if (!file_exists($destPath))
		{
			$document = f_util_DOMUtils::fromString('<panel use="' . $overridedBy . '" />');
			f_util_FileUtils::mkdir(dirname($destPath));
			f_util_DOMUtils::save($document, $destPath);
			$result['action'] = 'create';
		}
		return $result;
	}
	
	public function hasConfigFile($moduleName)
	{
		$path = change_FileResolver::getNewInstance()->getPath('modules', $moduleName, 'config', 'perspective.xml');
		return ($path !== null);
	}
	
	public function loadConfig($moduleName)
	{
		$doc = f_util_DOMUtils::fromString('<perspective><models /><toolbar /><actions /></perspective>');	
		$paths = change_FileResolver::getNewInstance()->getPaths('modules', $moduleName, 'config', 'perspective.xml');
		if (!count($paths))
		{
			Framework::warn(__METHOD__ . ' ' . $moduleName . ' has no perspective.xml');
			return array();	
		}
		
		foreach (array_reverse($paths) as $path) 
		{
			$this->appendPerspectiveConfig($path, $doc);
		}
		
		$config = array('modulename' => $moduleName);	
		$actions = array();
		$nodeList = $doc->getElementsByTagName('actions');
		if ($nodeList->length > 0)
		{
			$attrNames = array('permission', 'icon', 'actions', 'group', 'single', 'global', 'hidden');
			foreach ($nodeList->item(0)->getElementsByTagName('action') as $actionNode)
			{
				$actionInfos = array();
				$actionName = $actionNode->getAttribute("name");
				if ($actionNode->hasAttribute('labeli18n'))
				{
					$actionInfos['labeli18n'] = $actionNode->getAttribute('labeli18n');
				}
				elseif ($actionNode->hasAttribute('label'))
				{
					$label = $actionNode->getAttribute('label');
					$labLen = strlen($label);
					if ($labLen > 2 && $label[0] === '&' && $label[$labLen-1] === ';')
					{
						$actionInfos['labeli18n'] =  strtolower(substr($label, 1, $labLen-2));
					}
					else
					{
						$actionInfos['label'] = $label;
					}
				}
				else
				{
					$actionInfos['labeli18n'] =  strtolower('m.' . $moduleName . '.bo.actions.' . $actionName);
				}
				foreach ($attrNames as $attrName)
				{
					if ($actionNode->hasAttribute($attrName))
					{
						$actionInfos[$attrName] = $actionNode->getAttribute($attrName);
					}
				}
				$actions[$actionName] = $actionInfos;
			}
		}
		$config['actions'] = $actions;
		
		$toolbar = array();
		$nodeList = $doc->getElementsByTagName('toolbar');
		if ($nodeList->length > 0)
		{
			foreach ($nodeList->item(0)->getElementsByTagName('toolbarbutton') as $toolbarNode)
			{
				$toolbar[$toolbarNode->getAttribute("name")] = array();
			}
		}
		$config['toolbar'] = $toolbar;
		
		$models = array();
		$nodeList = $doc->getElementsByTagName('models');
		if ($nodeList->length > 0)
		{
			foreach ($nodeList->item(0)->getElementsByTagName('model') as $modelNode)
			{
				$model = array();
				
				$childList = $modelNode->getElementsByTagName('children');
				if ($childList->length > 0)
				{
					$model['children'] = array();
					foreach ($childList->item(0)->getElementsByTagName('child') as $childNode)
					{
						$name = $childNode->getAttribute("model");
						$from = $childNode->hasAttribute("from") ? $childNode->getAttribute("from") : 'treenode';
						$model['children'][$name] = $from;
					}
				}
				
				$dropList = $modelNode->getElementsByTagName('drops');
				if ($dropList->length > 0)
				{
					$drops = array();
					foreach ($dropList->item(0)->getElementsByTagName('drop') as $dropNode)
					{
						$drops[$dropNode->getAttribute("model")] = array('action' => $dropNode->getAttribute("action"));
					}
					$model['drops'] = $drops;
				}
				
				$nodes = $modelNode->getElementsByTagName('contextactions');
				if ($nodes->length > 0)
				{
					$contextactions = array();
					foreach ($nodes->item(0)->childNodes as $contextNode)
					{
						if ($contextNode->nodeType !== XML_ELEMENT_NODE)
						{
							continue;
						}
						$contextactions[$contextNode->getAttribute("name")] = array();
						if ($contextNode->nodeName === 'groupactions')
						{
							$name = $contextNode->getAttribute("name");
							$contextactions[$name]['actions'] = array();
							foreach ($contextNode->getElementsByTagName('contextaction') as $groupactionnode)
							{
								$contextactions[$name]['actions'][$groupactionnode->getAttribute("name")] = array();
							}
						}
					}
					$model['contextactions'] = $contextactions;
				}
				
				$nodes = $modelNode->getElementsByTagName('styles');
				if ($nodes->length > 0)
				{
					$model['styles'] = $nodes->item(0)->getAttribute("properties");
				}
				
				$nodes = $modelNode->getElementsByTagName('columns');
				if ($nodes->length > 0)
				{
					$columns = array();
					foreach ($nodes->item(0)->childNodes as $columnNode)
					{
						if ($columnNode->nodeType !== XML_ELEMENT_NODE)
						{
							continue;
						}
						$name = $columnNode->getAttribute('name');
						$columnInfos = array();
						if (!in_array($name, array('id', 'pub', 'label')))
							{
							$columnInfos['flex'] = ($columnNode->hasAttribute('flex')) ? $columnNode->getAttribute('flex') : 1;
							if ($columnNode->hasAttribute('label'))
							{
								$columnInfos['label'] = $columnNode->getAttribute('label');
							}
							elseif ($columnNode->hasAttribute('labeli18n'))
							{
								$columnInfos['labeli18n'] = strtolower($columnNode->getAttribute('labeli18n'));
							}
							else
							{
								$columnInfos['labeli18n'] = strtolower('m.' . $moduleName . '.bo.general.column.' . $name);
							}
						}
						if ($columnNode->hasAttribute("sortActive"))
						{
							$columnInfos['sortActive'] = ($columnNode->getAttribute("sortActive") == 'true');
							if ($columnInfos['sortActive'] && $columnNode->hasAttribute("sortDirection"))
							{
								$columnInfos['sortDirection'] = $columnNode->getAttribute("sortDirection");
							}
						}
						if ($columnNode->hasAttribute("hidden"))
						{
							$columnInfos['hidden'] = ($columnNode->getAttribute("hidden") == 'true');
						}
						$columns[$name] = $columnInfos;
					}
					$model['columns'] = $columns;
				}
				
				$models[$modelNode->getAttribute('name')] = $model;
			}
		}
		$config['models'] = $models;
		return $config;
	}
	
	/**
	 * @param string $path
	 * @param f_util_DOMDocument $document
	 */
	private function appendPerspectiveConfig($path, $document)
	{
		try 
		{
			$doc = f_util_DOMUtils::fromPath($path);
			
			$nodeTypes = array('models' => 'model', 'actions' => 'action', 'toolbar' => 'toolbarbutton');
			foreach ($nodeTypes as $pName => $cName) 
			{
				$parentNode = $document->getElementsByTagName($pName)->item(0);
				$parentSrcNode = $doc->getElementsByTagName($pName);
				if ($parentSrcNode->length > 0)
				{
					foreach ($parentSrcNode->item(0)->getElementsByTagName($cName) as $itemNode)
					{	
						$name = $itemNode->getAttribute('name');
						$newItemNode = $document->importNode($itemNode, true);
						$originalItemNode = $document->findUnique($cName . '[@name="'.$name .'"]', $parentNode);
						if ($originalItemNode !== null)
						{
							$parentNode->replaceChild($newItemNode , $originalItemNode);
						}
						else
						{
							$parentNode->appendChild($newItemNode);
						}	
					}
				}
			}

			$this->updatePerspectiveModelsConfig($document, $doc);
				
			$imports = $doc->getElementsByTagName('import');
			if ($imports->length > 0)
			{
				foreach ($imports as $importNode) 
				{
					$moduleName = $importNode->getAttribute('modulename');					
					$configname = $importNode->getAttribute('configfilename');
	 				if (f_util_StringUtils::isEmpty($configname))
	 				{
	 					$configname = 'perspective';
	 				}
	 				
					$paths = change_FileResolver::getNewInstance()->getPaths('modules', $moduleName, 'config', $configname .'.xml');
					if (!count($paths))
					{
						Framework::warn(__METHOD__ . " $moduleName/config/$configname.xml not found");
					}
					else
					{
						foreach (array_reverse($paths) as $path) 
						{
							$this->appendPerspectiveConfig($path, $document);
						}
					}
				}
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
	}
	
	/**
	 * @param f_util_DOMDocument $document
	 * @param f_util_DOMDocument $updateDoc
	 */
	private function updatePerspectiveModelsConfig($document, $updateDoc)
	{		
		$parentNode = $document->getElementsByTagName('models')->item(0);
		$parentSrcNode = $updateDoc->getElementsByTagName('models');
		if ($parentSrcNode->length > 0)
		{
			foreach ($parentSrcNode->item(0)->getElementsByTagName('updatemodel') as $modelNode)
			{	
				$name = $modelNode->getAttribute('name');
				$originalItemNode = $document->findUnique('model[@name="'.$name .'"]', $parentNode);				
				if ($originalItemNode !== null)
				{
					foreach ($modelNode->childNodes as $actionNode) 
					{
						if ($actionNode->nodeType !== XML_ELEMENT_NODE) {continue;}
						switch ($actionNode->nodeName) 
						{
							case 'addchild':
								$newChild = $document->createElement('child');
								$newChild->setAttribute('model', $actionNode->getAttribute('model'));
								if ($actionNode->hasAttribute('from'))
								{
									$newChild->setAttribute('from', $actionNode->getAttribute('from'));
								}
								$children = $document->findUnique('children', $originalItemNode);
								if ($children === null)
								{
									$children = $originalItemNode->appendChild($document->createElement('children'));
								}
								$children->appendChild($newChild);
								break;
							case 'adddrop':
								$newChild = $document->createElement('drop');
								$newChild->setAttribute('model', $actionNode->getAttribute('model'));
								$newChild->setAttribute('action', $actionNode->getAttribute('action'));
								$drops = $document->findUnique('drops', $originalItemNode);
								if ($drops === null)
								{
									$drops = $originalItemNode->appendChild($document->createElement('drops'));
								}
								$drops->appendChild($newChild);
								break;
							case 'addcontextaction':
								$newChild = $document->createElement('contextaction');
								$newChild->setAttribute('name', $actionNode->getAttribute('name'));
								$newChild->setAttribute('action', $actionNode->getAttribute('action'));
								$contextactions = $document->findUnique('contextactions', $originalItemNode);
								if ($contextactions === null)
								{
									$contextactions = $originalItemNode->appendChild($document->createElement('contextactions'));
								} 
								else if ($actionNode->hasAttribute('group'))
								{
									$group = $document->findUnique('groupactions[@name="' .$actionNode->getAttribute('group') . '"]', $contextactions);
									if ($group !== null)
									{
										$contextactions = $group; 				
									}
								}
								$contextactions->appendChild($newChild);
								break;
							case 'addcolumn' :
								$newChild = $document->createElement('column');
								$newChild->setAttribute('name', $actionNode->getAttribute('name'));
								
								if ($actionNode->hasAttribute('labeli18n'))
								{
									$newChild->setAttribute('labeli18n', $actionNode->getAttribute('labeli18n'));
								}
								elseif ($actionNode->hasAttribute('label'))
								{
									$newChild->setAttribute('label', $actionNode->getAttribute('label'));
								}
								
								if ($actionNode->hasAttribute('flex'))
								{
									$newChild->setAttribute('flex', $actionNode->getAttribute('flex'));
								}
								if ($actionNode->hasAttribute('sortActive'))
								{
									$newChild->setAttribute('sortActive', $actionNode->getAttribute('sortActive'));
								}
								if ($actionNode->hasAttribute('sortDirection'))
								{
									$newChild->setAttribute('sortDirection', $actionNode->getAttribute('sortDirection'));
								}
								if ($actionNode->hasAttribute('hidden'))
								{
									$newChild->setAttribute('hidden', $actionNode->getAttribute('hidden'));
								}
								
								$columns = $document->findUnique('columns', $originalItemNode);
								if ($columns === null)
								{
									$columns = $originalItemNode->appendChild($document->createElement('columns'));
									$columns->appendChild($newChild);
								}
								else if ($actionNode->hasAttribute('before'))
								{
									$before = $document->findUnique('column[@name="' . $actionNode->getAttribute('before') . '"]', $columns);
									if ($before !== null)
									{
										$columns->insertBefore($newChild, $before);
									}
									else
									{
										$columns->appendChild($newChild);
									}
								}
								else
								{
									$oldNode = $document->findUnique('column[@name="' .$newChild->getAttribute('name') . '"]', $columns);
									if ($oldNode !== null)
									{
										$columns->replaceChild($newChild, $oldNode);
									}
									else
									{
										$columns->appendChild($newChild);
									}
								}
								break;
							case 'addstyles' :
								$stylesElem = $document->findUnique('styles', $originalItemNode);
								if ($stylesElem === null)
								{
									$stylesElem = $document->createElement("styles");
									$stylesElem->setAttribute("properties", $actionNode->getAttribute("properties"));
									$originalItemNode->appendChild($stylesElem);
								}
								else
								{
									$stylesProps = array_unique(array_merge(explode(" ", $stylesElem->getAttribute("properties")), explode(" ", $actionNode->getAttribute("properties"))));
									$stylesElem->setAttribute("properties", implode(" ", $stylesProps));
								}
								break;
						}
					}	
				}
				else
				{
					Framework::warn(__METHOD__ . ' Original model ' . $name. 'does not exist');
				}
			}
		}			
	}
	
	public function convertToJSON($config)
	{
		$result = array('actions' => array(), 'toolbar' => $config['toolbar'], 'models' => array());
		$ls = LocaleService::getInstance();
		foreach ($config['actions'] as $name => $infos)
		{
			$hidden = (isset($infos['hidden']) && $infos['hidden'] === 'true');
			if ($hidden) {continue;}
			
			$action = array('name' => $name);
			if (isset($infos['labeli18n']))
			{
				$action['label'] = $ls->trans($infos['labeli18n'], array('ucf'));
			}
			else
			{
				$action['label'] = $infos['label'];
			}
			
			if (isset($infos['permission']))
			{
				$action['permission'] = $infos['permission'];
			}
			if (isset($infos['single']))
			{
				$action['single'] = true;
			}
			if (isset($infos['global']))
			{
				$action['global'] = true;
			}
			
			//, MediaHelper::IMAGE_PNG, MediaHelper::LAYOUT_SHADOW
			if (isset($infos['icon']))
			{
				$action['icon'] = MediaHelper::getIcon($infos['icon'], MediaHelper::SMALL);
			}
			$result['actions'][$name] = $action;
		}
		
		$container = array();
		foreach ($config['models'] as $name => $infos)
		{
			if (isset($infos['children']))
			{
				$container[$name] = array('c' => true);
			}
		}
		
		foreach ($container as $name => $infos)
		{
			foreach (array_keys($config['models'][$name]['children']) as $subname)
			{
				if (isset($container[$subname]))
				{
					$container[$name]['cc'] = true;
					break;
				}
			}
		}
		
		foreach ($config['models'] as $name => $infos)
		{
			$model = array('name' => $name, 'type' => str_replace('/', '_', $name));
			try 
			{
				$documentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($name);
				$modelInfos = array();
				$modelInfos['useCorrection'] = $documentModel->useCorrection();
				$modelInfos['hasWorkflow'] = workflow_ModuleService::getInstance()->hasPublishedWorkflowByModel($documentModel);
				if ($documentModel->hasWorkflow())
				{
					$modelInfos['workflowStartTask'] = $documentModel->getWorkflowStartTask();
				}
				$modelInfos['isInternationalized'] = $documentModel->isLocalized();
				$model['infos'] = $modelInfos;
			}
			catch (Exception $e)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::error(__METHOD__ . ' ' . $name . ' has no documentModel');
					Framework::exception($e);
				}
			}
			
			if (isset($infos['drops']))
			{
				$model['drops'] = $infos['drops'];
				
			}

			if (isset($container[$name]))
			{
				$model = array_merge($model, $container[$name]);
			}
			if (isset($infos['contextactions']))
			{
				$model['contextactions'] = $infos['contextactions'];
			}
			else
			{
				$model['contextactions'] = array();
			}
			if (isset($infos['children']))
			{
				$model['children'] = array();
				foreach ($infos['children'] as $subtype => $from)
				{
					if (isset($container[$subtype]))
					{
						$model['children'][$subtype] = $container[$subtype];
					}
					else
					{
						$model['children'][$subtype] = array('i' => true);
					}
					$model['children'][$subtype]['from'] = $from;
				}
			
			}
			if (isset($infos['columns']))
			{
				$model['columns'] = array();
				foreach ($infos['columns'] as $columnName => $info)
				{
					if (isset($info['labeli18n']))
					{
						$info['label'] = $ls->trans($info['labeli18n'], array('ucf'));
						unset($info['labeli18n']);
					}
					$model['columns'][$columnName] = $info;
				}
			}
			
			if (isset($infos['styles']))
			{
				$model['styles'] = $infos['styles'];
			}
			
			$result['models'][$name] = $model;
		}
		
		return JsonService::getInstance()->encode($result);
	}
	
	public function buildModuleBinding($moduleName, $config)
	{
		$extends = uixul_lib_BindingObject::getUrl('core.cModule', false) . '#cModule';
		$rq = RequestContext::getInstance();
		$rq->beginI18nWork($rq->getUILang());
		
		$actionsDoc = f_util_DOMUtils::fromString('<actions />');
		$this->getActionsConfig($moduleName, $actionsDoc);	
		$methodArray = $this->generateMethodes($actionsDoc);
		$handlerArray = $this->generateHandler($actionsDoc);

		$templateObject = change_TemplateLoader::getNewInstance()->setExtension('xul')
			->load('modules', $moduleName, 'templates', 'perspectives', 'default');
		
		$tagReplacer = new f_util_TagReplacer();
			
		$moduleContents = $tagReplacer->run($templateObject->execute(), true);
		$this->translateAnonidToId($moduleName, $moduleContents);
		
		$templateObject = change_TemplateLoader::getNewInstance()->setExtension('xml')
				->load('modules', 'uixul', 'templates', 'Uixul-cModule-Binding');
		
		$initCodeArray = array();
		
		$initCodeArray[] = 'this.mConfig = '. uixul_ModuleBindingService::getInstance()->convertToJSON($config);
		$initCodeArray[] = 'this.mRootFolderId = '. ModuleService::getInstance()->getRootFolderId($moduleName);
		$templateObject->setAttribute('init', "<![CDATA[\n" . join(";\n", $initCodeArray) . "]]>\n");
		$templateObject->setAttribute('bindingId', 'wModule-' . $moduleName);
		$templateObject->setAttribute('extends', $extends);
		$templateObject->setAttribute('methods', join(PHP_EOL, $methodArray));
		$templateObject->setAttribute('moduleContents', $moduleContents);
		if (count($handlerArray))
		{
			$templateObject->setAttribute('handlers', join(PHP_EOL, $handlerArray));
		}
		else
		{
			$templateObject->setAttribute('handlers', false);
		}
		
		$xml = $templateObject->execute();
		$xml = str_replace(array('{HttpHost}', '{IconsBase}'), 
							array(Framework::getUIBaseUrl(), MediaHelper::getIconBaseUrl()), $xml);
		$rq->endI18nWork();
		
		return $xml;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $contents
	 */
	private function translateAnonidToId($moduleName, &$contents)
	{
		$prefix = '<?xml version="1.0" encoding="UTF-8"?><root>';
		$suffix = '</root>';		
		$xmlData = new DOMDocument();
		$xmlData->loadXML($prefix . $contents . $suffix);
		$xpathObject = new DOMXPath($xmlData);
		$widgetNodes = $xpathObject->query("//*[@id]");
		foreach ($widgetNodes as $widgetNode)
		{
			if ($widgetNode->hasAttribute("anonid")) {continue;}

			$widgetId = $widgetNode->getAttribute("id");
			$widgetNode->setAttribute("id", "modules_".$moduleName."_widget_".$widgetId);
			$widgetNode->setAttribute("anonid", $widgetId);			
		}
			
		$widgetNodes = $xpathObject->query("//*[@attachment]");
		foreach ($widgetNodes as $widgetNode)
		{
			$attachmentIds = array();
			$attachmentArray = explode(" ", $widgetNode->getAttribute("attachment"));
			foreach ($attachmentArray as $attachment)
			{
				if (trim($attachment))
				{
					$attachmentIds[] = "modules_".$moduleName."_widget_".$attachment;
				}
			}
			if (count($attachmentIds) > 0)
			{
				$widgetNode->setAttribute("attachment", join(" ", $attachmentIds));
			}		
		}
		$contents = substr($xmlData->SaveXML(), strlen($prefix)+1, -strlen($suffix)-1);
	}
	
	/**
	 * @param string $moduleName
	 * @param DOMDocument $document
	 */
	private function getActionsConfig($moduleName, $document, $configFileName = 'actions')
	{
		$actionsFilePaths = change_FileResolver::getNewInstance()->getPaths('modules', $moduleName, 'config', $configFileName . '.xml');
		if (!count($actionsFilePaths))
		{
			Framework::info('No ' . $configFileName . '.xml in module ' . $moduleName);
			return;
		}
		foreach (array_reverse($actionsFilePaths) as $path) 
		{
			try 
			{
			 	$doc = f_util_DOMUtils::fromPath($path);
			 	foreach ($doc->documentElement->childNodes as $node) 
			 	{
			 		if ($node->nodeType === XML_ELEMENT_NODE)
			 		{
			 			if ($node->nodeName == 'import')
			 			{
			 				$configname = $node->getAttribute('configfilename');
			 				if (f_util_StringUtils::isEmpty($configname))
			 				{
			 					$configname = 'actions';
			 				}
			 				$this->getActionsConfig($node->getAttribute('modulename'), $document, $configname);
			 			}
			 			else
			 			{
							$impNode = $document->importNode($node, true);
							$document->documentElement->appendChild($impNode);
			 			}
			 		}
			 	} 
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}	
		}
	}
	
	/**
	 * @param DOMDocument $domDocument
	 * @return array
	 */
	private function generateHandler($domDocument)
	{
		$tagReplacer = new f_util_TagReplacer();
		$result = array();
		foreach ($domDocument->getElementsByTagName('handler') as $domHandler) 
		{
			if (!$domHandler->hasAttribute('event')) {continue;}
			$data = $domDocument->saveXML($domHandler);
			$result[] = $tagReplacer->run($data, true);
		}		
		return $result;
	}
	
	/**
	 * @param DOMDocument $domDocument
	 * @return array
	 */
	private function generateMethodes($domDocument)
	{
		$tagReplacer = new f_util_TagReplacer();
		$result = array();
		foreach ($domDocument->getElementsByTagName('action') as $domAction) 
		{
			$name = $domAction->getAttribute('name');
			$method = $domDocument->createElement('method');
			$method->setAttribute('name', $name);
			foreach ($domAction->childNodes as $domChild) 
			{
				if ($domChild->nodeType === XML_ELEMENT_NODE 
					&& ($domChild->nodeName === 'parameter' || $domChild->nodeName === 'body'))
			 	{
			 		$method->appendChild($domChild->cloneNode(true));  	
			 	}
			}
			$data = $domDocument->saveXML($method);
			$result[$name] = $tagReplacer->run($data, true);
		}		
		return $result;
	}
	
	private function buildMethods($actionArray)
	{
		$methodArray = array();
		
		$tagReplacer = new f_util_TagReplacer();
		foreach ($actionArray as $actionObject)
		{
			if ($actionObject->body)
			{
				$body = str_replace('%label%', $actionObject->label, $actionObject->body);
				$body = $tagReplacer->run($body, true);
				$parameters = array();
				foreach ($actionObject->parameters as $parameterName)
				{
					$parameters[] = '<parameter name="'.$parameterName.'" />';
				}
				$methodArray[] = "<method name=\"".$actionObject->name."\">\n".join(PHP_EOL, $parameters)."\n<body><![CDATA[\n".$body."\n]]>\n</body>\n</method>\n\n";
			}
		}
		
		return $methodArray;
	}
}