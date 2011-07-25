<?php
class uixul_ModuleBindingService extends BaseService
{
	/**
	 * @var uixul_ModuleBindingService
	 */
	private static $instance;
	
	/**
	 * @return uixul_ModuleBindingService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param string $moduleName
	 * @return DOMDocument
	 */
	public function getConvertedConfig($moduleName)
	{
		$paths = $this->getConfigFiles('uixul');
		$paths = array_merge($paths, $this->getConfigFiles($moduleName));
		
		$path = FileResolver::getInstance()->setPackageName('framework')->setDirectory('config')->getPath('rights.xml');
		$paths['defaultrights'] = $path;
		$path = FileResolver::getInstance()->setPackageName('modules_uixul')->setDirectory('config')->getPath('actions.xml');
		$paths['defaultactions'] = $path;
		
		$path = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('config')->getPath('rights.xml');
		if ($path)
		{
			$paths['rights'] = $path;
		}
		$path = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('config')->getPath('actions.xml');
		if ($path)
		{
			$paths['actions'] = $path;
		}
		
		$oldConfig = $this->buildCompositeConfig($paths);
		
		//		echo $oldConfig->saveXML();
		//		return;
		$xpath = new DOMXPath($oldConfig);
		
		$models = $this->extractModelsName($moduleName, $xpath);
		$allActions = array();
		foreach ($models as $modelName => $data)
		{
			$actions = $this->extractActions($modelName, $xpath);
			foreach ($actions as $actionName => $data)
			{
				if (isset($allActions[$actionName]))
				{
					$allActions[$actionName]['toolbar'] += $data['toolbar'];
				}
				else
				{
					$allActions[$actionName] = array_merge($data, $this->extractActionInfos($actionName, $xpath));
				}
			}
			$models[$modelName]['actions'] = $actions;
			$drops = $this->extractDrop($modelName, $xpath);
			if (count($drops) > 0)
			{
				$models[$modelName]['children'] = array();
				foreach ($drops as $model => $drop)
				{
					$models[$modelName]['children'][$model] = array();
					$allActions[$drop['action']] = array_merge(array('toolbar' => 0), $this->extractActionInfos($drop['action'], $xpath));
				}
				$models[$modelName]['drops'] = $drops;
			}
			if (! isset($models[$modelName]['children']))
			{
				foreach (array_keys($actions) as $actionName)
				{
					if (isset($allActions[$actionName]['permission']) && $allActions[$actionName]['permission'] === 'Insert')
					{
						$models[$modelName]['children'] = array('modules_generic/Document' => array());
					}
				}
			}
		}
		
		$columns = $this->extractColumnInfos($xpath);
		if (count($columns) > 0)
		{
			$styles = $this->extractStyleInfos($xpath);
			foreach ($columns as $formodelname => $columnlist)
			{
				if (! isset($models[$formodelname]))
				{
					continue;
				}
				if (! isset($models[$formodelname]['children']))
				{
					$models[$formodelname]['children'] = array('modules_generic/Document' => array());
				}
				$models[$formodelname]['styles'] = implode(' ', $styles);
				$models[$formodelname]['columns'] = $columnlist;
			}
		}
		
		$globalActions = $this->extractGlogal($xpath);
		foreach ($globalActions as $name => $info)
		{
			if (! isset($allActions[$name]))
			{
				$allActions[$name] = array_merge(array('toolbar' => 0), $info);
			}
		}
		
		$doc = new DOMDocument('1.0', 'UTF-8');
		$doc->loadXML('<perspective/>');
		$doc->formatOutput = true;
		$modelnodes = $doc->documentElement->appendChild($doc->createElement('models'));
		
		foreach ($models as $modelName => $data)
		{
			$model = $modelnodes->appendChild($doc->createElement('model'));
			$model->setAttribute('name', $modelName);
			
			if (isset($data['children']))
			{
				$children = $model->appendChild($doc->createElement('children'));
				foreach ($data['children'] as $childrenmodelname => $childrenInfo)
				{
					$child = $children->appendChild($doc->createElement('child'));
					$child->setAttribute('model', $childrenmodelname);
					if (isset($childrenInfo['action']))
					{
						$child->setAttribute('dropaction', $childrenInfo['action']);
					}
				}
			}
			if (isset($data['drops']))
			{
				$children = $model->appendChild($doc->createElement('drops'));
				foreach ($data['drops'] as $modelname => $dropInfo)
				{
					$child = $children->appendChild($doc->createElement('drop'));
					$child->setAttribute('model', $modelname);
					$child->setAttribute('action', $dropInfo['action']);
				}
			}
			
			if (isset($data['columns']))
			{
				if (isset($data['styles']))
				{
					$styles = $model->appendChild($doc->createElement('styles'));
					$styles->setAttribute('properties', $data['styles']);
				}
				$columns = $model->appendChild($doc->createElement('columns'));
				foreach ($data['columns'] as $columnname => $columnInfo)
				{
					$column = $columns->appendChild($doc->createElement('column'));
					$column->setAttribute('name', $columnname);
					foreach ($columnInfo as $attrname => $val)
					{
						$column->setAttribute($attrname, $val);
					}
				}
			}
			
			$contextactions = $model->appendChild($doc->createElement('contextactions'));
			$groupaction = null;
			foreach (array_keys($data['actions']) as $actionname)
			{
				$actionInfo = $allActions[$actionname];
				if (isset($actionInfo['actions']))
				{
					$groupaction = $contextactions->appendChild($doc->createElement('groupactions'));
					$groupaction->setAttribute('name', $actionname);
				}
				else if (isset($actionInfo['group']))
				{
					$contextaction = $groupaction->appendChild($doc->createElement('contextaction'));
					$contextaction->setAttribute('name', $actionname);
				}
				else
				{
					$contextaction = $contextactions->appendChild($doc->createElement('contextaction'));
					$contextaction->setAttribute('name', $actionname);
				}
			}
		}
		
		$toolbarLimit = count($models) * 0.5;
		$toolbar = $doc->documentElement->appendChild($doc->createElement('toolbar'));
		foreach ($allActions as $actionName => $infos)
		{
			if (isset($infos['global']) || (isset($infos['toolbar']) &&  $infos['toolbar'] > $toolbarLimit))
			{
				$toolbarbutton = $toolbar->appendChild($doc->createElement('toolbarbutton'));
				$toolbarbutton->setAttribute('name', $actionName);
			}
		}
		
		$actions = $doc->documentElement->appendChild($doc->createElement('actions'));
		$attrNames = array('single', 'global', 'permission', 'actions', 'group', 'icon', 'label');
		foreach ($allActions as $actionName => $infos)
		{
			$action = $actions->appendChild($doc->createElement('action'));
			$action->setAttribute('name', $actionName);
			foreach ($attrNames as $attrname)
			{
				if (isset($infos[$attrname]))
				{
					if ($attrname === 'single' || $attrname === 'global')
					{
						$action->setAttribute($attrname, 'true');
					}
					else
					{
						$action->setAttribute($attrname, $infos[$attrname]);
					}
				}
			}
			switch ($actionName) 
			{
				case 'edit':
					$action->setAttribute('icon', 'edit');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.edit');
					break;
				case 'delete':
					$action->setAttribute('icon', 'delete');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.delete');
					break;
				case 'openFolder':
					$action->setAttribute('icon', 'open-folder');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.openfolder');
					break;
				case 'duplicate':
					$action->setAttribute('icon', 'duplicate');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.duplicate');
					break;	
				case 'createFolder':
					$action->setAttribute('icon', 'create-folder');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.create-folder');
					break;								
				case 'reactivate':
					$action->setAttribute('icon', 'reactivate');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.reactivate');
					break;	
				case 'deactivated':
					$action->setAttribute('icon', 'deactivated');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.deactivate;');
					break;	
				case 'activate':
					$action->setAttribute('icon', 'activate');
					$action->setAttribute('labeli18n', 'm.uixul.bo.actions.activate;');
					break;				
			}
		}
		
		return $doc;
	}
	
	private function getConfigFiles($moduleName)
	{
		$widgetPaths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory('config/widgets')->getPaths('');
		$paths = array();
		foreach ($widgetPaths as $widgetsConfigDir)
		{
			$dirObject = dir($widgetsConfigDir);
			$entry = $dirObject->read();
			while ($entry)
			{
				if (strpos($entry, '.xml') !== false)
				{
					$widgetId = substr($entry, 0, strrpos($entry, '.'));
					if ($widgetId !== 'selectorTree' && $widgetId !== 'selectorList')
					{
						if (! array_key_exists($widgetId, $paths))
						{
							$paths[$widgetId] = $widgetsConfigDir . $entry;
						}
					}
				}
				$entry = $dirObject->read();
			}
			$dirObject->close();
		}
		
		return $paths;
	}
	
	private function buildCompositeConfig($paths)
	{
		$domDoc = new DOMDocument('1.0', 'UTF-8');
		$domDoc->loadXML('<compositeconfig />');
		
		foreach ($paths as $name => $path)
		{
			$domconf = new DOMDocument('1.0', 'UTF-8');
			if (! $domconf->load($path))
			{
				$domconf->loadXML('<error />');
			}
			$node = $domDoc->importNode($domconf->documentElement, true);
			$node->setAttribute('oldconfigname', $name);
			$node->setAttribute('oldconfigpath', $path);
			$domDoc->documentElement->appendChild($node);
		}
		
		return $domDoc;
	}
	
	/**
	 * @param string $moduleName
	 * @param DOMXPath $xpath
	 * @return array
	 */
	private function extractModelsName($moduleName, $xpath)
	{
		$result = array();
		$nodes = $xpath->query('//datasource/@components');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$components = explode(',', $nodes->item($i)->nodeValue);
			foreach ($components as $component)
			{
				if (strpos($component, '/') === false)
				{
					$component = 'modules_' . $moduleName . '/' . $component;
				}
				$result[$component] = array();
			}
		}
		
		$nodes = $xpath->query('//event/@target');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$targets = explode(' ', $nodes->item($i)->nodeValue);
			foreach ($targets as $target)
			{
				$targetparts = explode('_', trim($target));
				if (count($targetparts) === 3 && ($targetparts[1] === $moduleName || $targetparts[2] === 'rootfolder' || $targetparts[2] === 'systemfolder' || $targetparts[2] === 'folder'))
				{
					$component = $targetparts[0] . '_' . $targetparts[1] . '/' . $targetparts[2];
					$result[$component] = array();
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * @param string $modelName
	 * @param DOMXPath $xpath
	 * @return array
	 */
	private function extractActions($modelName, $xpath)
	{
		$result = array();
		$mn = str_replace('/', '_', $modelName);
		$nodes = $xpath->query('//event[@target="*" or contains(@target, "' . $mn . '")]/@actions');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$components = explode(' ', $nodes->item($i)->nodeValue);
			$type = $nodes->item($i)->parentNode->getAttribute("type");
			if ($type === 'drop')
			{
				continue;
			}
			$toolbar = strpos($type, 'select') !== false;
			foreach ($components as $component)
			{
				$component = trim($component);
				if ($component === '' || $component === '|')
				{
					continue;
				}
				if (strrpos($component, '_') === strlen($component) - 1)
				{
					
					$subnodes = $xpath->query('actiongroup[@name="' . $component . '"]/@actions', $nodes->item($i)->parentNode);
					if ($subnodes->length === 1)
					{
						$group = array();
						$result[$component] = array('toolbar' => 0);
						$actions = explode(' ', $subnodes->item(0)->nodeValue);
						foreach ($actions as $action)
						{
							$action = trim($action);
							if ($action === '' || $action === '|')
							{
								continue;
							}
							$group[] = $action;
							if (! isset($result[$action]))
							{
								$result[$action] = array('toolbar' => 0, 'group' => $component);
							}
							if ($toolbar)
							{
								$result[$action]['toolbar'] += 1;
							}
						}
						
						$result[$component]['actions'] = join(',', $group);
						$actiongroup = $subnodes->item(0)->parentNode;
						if ($actiongroup->hasAttribute('icon'))
						{
							$result[$component]['icon'] = $actiongroup->getAttribute('icon');
						}
						else
						{
							$result[$component]['icon'] = 'document';
						}
						if ($actiongroup->hasAttribute('label'))
						{
							$result[$component]['label'] = $actiongroup->getAttribute('label');
						}
					}
					else
					{
						echo "not found actiongroup :$component \n";
					}
				}
				else if (strpos($component, '-') === 0)
				{
					unset($result[substr($component, 1)]);
				}
				else
				{
					if (! isset($result[$component]))
					{
						$result[$component] = array('toolbar' => 0);
					}
					if ($toolbar)
					{
						$result[$component]['toolbar'] += 1;
					}
				}
			}
		}
		return $result;
	}
	
	private function extractGlogal($xpath)
	{
		$result = array();
		$nodes = $xpath->query('//actions/action[@global="true"]');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$node = $nodes->item($i);
			$name = $node->getAttribute('name');
			if (! isset($result[$name]))
			{
				$actionInfo = $this->extractActionInfos($name, $xpath);
				if ($actionInfo['global'])
					$result[$name] = $actionInfo;
			}
		}
		return $result;
	}
	
	/**
	 * @param string $modelName
	 * @param DOMXPath $xpath
	 * @return array
	 */
	private function extractDrop($modelName, $xpath)
	{
		$result = array();
		$mn = str_replace('/', '_', $modelName);
		$nodes = $xpath->query('//event[@type="drop"]');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$node = $nodes->item($i);
			$target = $node->getAttribute('target');
			if (strpos($target, $mn) !== false)
			{
				$sources = explode(' ', $node->getAttribute('source'));
				foreach ($sources as $source)
				{
					$srcparts = explode('_', trim($source));
					if (count($srcparts) === 3)
					{
						$result[$srcparts[0] . '_' . $srcparts[1] . '/' . $srcparts[2]] = array('action' => $node->getAttribute('actions'));
					}
				}
			}
		}
		return $result;
	}
	
	private function extractActionInfos($actionName, $xpath)
	{
		$result = array();
		$nodes = $xpath->query('//action[@back-office-name="' . $actionName . '" ]/@name');
		if ($nodes->length > 0)
		{
			$result['permission'] = $nodes->item(0)->nodeValue;
			if ($result['permission'] == 'Insert')
			{
				$result['permission'] .= '_' . $nodes->item(0)->parentNode->parentNode->getAttribute('name');
			}
		}
		else
		{
			$nodes = $xpath->query('//rights//action[@name="' . ucfirst($actionName) . '" ]/@name');
			if ($nodes->length > 0)
			{
				$result['permission'] = $nodes->item(0)->nodeValue;
			}
		}
		$nodes = $xpath->query('//actions/action[@name="' . $actionName . '"]/@icon');
		if ($nodes->length > 0)
		{
			$result['icon'] = $nodes->item(0)->nodeValue;
		}
		
		$nodes = $xpath->query('//actions/action[@name="' . $actionName . '"]/@label');
		if ($nodes->length > 0)
		{
			$result['label'] = $nodes->item(0)->nodeValue;
		}
		
		$nodes = $xpath->query('//actions/action[@name="' . $actionName . '"]/@selectionType');
		if ($nodes->length > 0)
		{
			$value = $nodes->item($nodes->length - 1)->nodeValue;
			if ($value === 'single')
			{
				$result['single'] = true;
			}
		}
		
		$nodes = $xpath->query('//actions/action[@name="' . $actionName . '"]/@global');
		if ($nodes->length > 0)
		{
			$value = $nodes->item($nodes->length - 1)->nodeValue;
			if ($value === 'true')
			{
				$result['global'] = true;
			}
		}
		
		$nodes = $xpath->query('//actions/action[@name="' . $actionName . '"]/@global');
		if ($nodes->length > 0)
		{
			$value = $nodes->item($nodes->length - 1)->nodeValue;
			if ($value === 'true')
			{
				$result['global'] = true;
			}
		}
		return $result;
	}
	
	private function extractStyleInfos($xpath)
	{
		$result = array();
		$nodes = $xpath->query('//column/@properties');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$styleInfos = explode(' ', $nodes->item($i)->nodeValue);
			foreach ($styleInfos as $style)
			{
				$style = trim($style);
				if ($style === '' || $style === 'type')
				{
					continue;
				}
				$result[$style] = true;
			}
		}
		return array_keys($result);
	}
	
	private function extractColumnInfos($xpath)
	{
		$result = array();
		$nodes = $xpath->query('//columns/@for-parent-type');
		for($i = 0; $i < $nodes->length; $i ++)
		{
			$typeparts = explode('_', $nodes->item($i)->nodeValue);
			$data = array();
			$columns = $nodes->item($i)->parentNode;
			foreach ($columns->getElementsByTagName('column') as $column)
			{
				$name = $column->getAttribute("ref");
				if ($name === 'label')
				{
					continue;
				}
				$label = $column->getAttribute("label");
				$flex = $column->getAttribute("flex");
				$data[$name] = array('label' => ($label) ? $label : $name, 'flex' => ($flex) ? $flex : 1);
			}
			if (count($data) > 0)
			{
				$result[$typeparts[0] . '_' . $typeparts[1] . '/' . $typeparts[2]] = $data;
			}
		}
		return $result;
	}
	
	public function addImportInPerspective($forModuleName, $fromModuleName, $configFileName)
	{
		$destPath = f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config', 'perspective.xml');
		$result = array('action' => 'ignore', 'path' => $destPath);
		
		$path = FileResolver::getInstance()->setPackageName('modules_' . $fromModuleName)
			->setDirectory('config')->getPath($configFileName .'.xml');
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
		
		$path = FileResolver::getInstance()->setPackageName('modules_' . $fromModuleName)
			->setDirectory('config')->getPath($configFileName .'.xml');
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
		$path = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)
			->setDirectory('config')->getPath('perspective.xml');
		return ($path !== null);
	}
	
	public function loadConfig($moduleName)
	{
		$doc = f_util_DOMUtils::fromString('<perspective><models /><toolbar /><actions /></perspective>');	
		$paths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)
			->setDirectory('config')->getPaths('perspective.xml');
		if ($paths === null)
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
						$name = $columnNode->getAttribute("name");
						$columnInfos = array();
						$columnInfos['flex'] = ($columnNode->hasAttribute("flex")) ? $columnNode->getAttribute("flex") : 1;
						if ($columnNode->hasAttribute("label"))
						{
							$label = $columnNode->getAttribute('label');
							$labLen = strlen($label);
							if ($labLen > 2 && $label[0] === '&' && $label[$labLen-1] === ';')
							{
								$columnInfos['labeli18n'] =  strtolower(substr($label, 1, $labLen-2));
							}
							else
							{
								$columnInfos['label'] = $label;
							}
						}
						else
						{
							$columnInfos['labeli18n'] = strtolower('m.' . $moduleName . '.bo.general.column.' . $name);
						}

						$columns[$name] = $columnInfos;
					}
					$model['columns'] = $columns;
				}
				
				$models[$modelNode->getAttribute("name")] = $model;
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
	 				
					$paths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)
						->setDirectory('config')->getPaths($configname .'.xml');
					if ($paths === null)
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
				$action['label'] = $ls->transBO($infos['labeli18n'], array('ucf'));
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
				$modelInfos['hasWorkflow'] = $documentModel->hasWorkflow();
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
						$info['label'] = $ls->transBO($info['labeli18n'], array('ucf'));
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

		$templateLoader = TemplateLoader::getInstance();
		
		$templateObject = $templateLoader->setMimeContentType(K::XUL)
			->setDirectory('templates/perspectives')
			->setPackageName('modules_' . $moduleName)
			->load(K::DEFAULT_PERSPECTIVE_NAME);
		
		$tagReplacer = new f_util_TagReplacer();
			
		$moduleContents = $tagReplacer->run($templateObject->execute(), true);
		uixul_lib_UiService::translateAnonidToId($moduleName, $moduleContents);
		
		$templateObject = $templateLoader->reset()
				->setPackageName('modules_uixul')->setMimeContentType(K::XML)
				->load('Uixul-cModule-Binding');
		
		$initCodeArray = array();
		
		$initCodeArray[] = 'this.mConfig = '. uixul_ModuleBindingService::getInstance()->convertToJSON($config);
		$initCodeArray[] = 'this.mRootFolderId = '. ModuleService::getInstance()->getRootFolderId($moduleName);
		$templateObject->setAttribute('init', "<![CDATA[\n" . join(";\n", $initCodeArray) . "]]>\n");
		$templateObject->setAttribute('bindingId', 'wModule-' . $moduleName);
		$templateObject->setAttribute('extends', $extends);
		$templateObject->setAttribute('methods', join(K::CRLF, $methodArray));
		$templateObject->setAttribute('moduleContents', $moduleContents);
		if (count($handlerArray))
		{
			$templateObject->setAttribute('handlers', join(K::CRLF, $handlerArray));
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
	 * @param DOMDocument $document
	 */
	private function getActionsConfig($moduleName, $document, $configFileName = 'actions')
	{
		$actionsFilePaths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)
						->setDirectory('config')->getPaths($configFileName . '.xml');
		if (!is_array($actionsFilePaths))
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
				$methodArray[] = "<method name=\"".$actionObject->name."\">\n".join(K::CRLF, $parameters)."\n<body><![CDATA[\n".$body."\n]]>\n</body>\n</method>\n\n";
			}
		}
		
		return $methodArray;
	}
}