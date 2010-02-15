<?php
class uixul_GetBindingCoreView extends f_view_BaseView
{
	const SETUP_ACTIONS_METHOD    = '__setupActions';
	const SETUP_COLUMNS_METHOD    = '__setupColumns';
	const SETUP_DATASOURCE_METHOD = '__setupDatasource';
	
	protected function sendHttpHeaders()
	{
	}
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
        $rq = RequestContext::getInstance();

        $rq->beginI18nWork($rq->getUILang());

		$this->setTemplateName('Uixul-Raw', K::XML, 'uixul');

		$binding    = $request->getParameter('binding');

		$widgetName = null;

	    if (substr($binding, 0, strpos($binding, '.')) == 'modules')
	    {
	        list(, $modName, $bindingName) = explode('.', $binding);
               $className = "" . $modName . "_lib_" . $bindingName . "TagReplacer";
	    }
	    else
	    {
	    	$widgetName = substr($binding, strrpos($binding, '.') + 1);
               $className  = "uixul_lib_" . $widgetName . "TagReplacer";
	    }
	    
		if (f_util_ClassUtils::classExists($className))
		{
	    	$tagReplacer = new $className();
		}
		else
		{
			$tagReplacer = new f_util_TagReplacer();
		}


		$templateObject = new TemplateObject($request->getAttribute('bindingFile'), K::XML);
		$xml = $templateObject->execute();

		$tagReplacer->setReplacement('ControllerUrl', Framework::getUIBaseUrl() . "/xul_controller.php");
		$tagReplacer->setReplacement('HttpHost', Framework::getUIBaseUrl());
		$xml = $tagReplacer->run($xml, true);

        // SebC - 12/04/2006 - add support for more than one binding into a single file :
		$matches = array();
		preg_match_all('/extends="([^\"]*)"/', $xml, $matches, PREG_SET_ORDER);
		if ( ! empty($matches) )
		{
		    foreach ($matches as $match) {
    			$extendsAttribute = $match[0];
    			$extendsAttributeValue = $match[1];
    			if (!preg_match('/^\w+:/i', $extendsAttributeValue))
    			{
	    			list($bindingFile, $bindingId) = explode('#', $extendsAttributeValue, 2);
	    			$getBindingUrl = uixul_lib_BindingObject::getUrl($bindingFile, true);
	    			$xml = str_replace($extendsAttribute, 'extends="' . $getBindingUrl . '#' . $bindingId . '"', $xml);
    			}
		    }
		}

		// SebC - 16/05/2006 - add support for stylesheet resources :
		$matches = array();
		preg_match_all('/<stylesheet\ssrc="([^\"]*)"\s*\/>/', $xml, $matches, PREG_SET_ORDER);
		if ( ! empty($matches) )
		{
		    foreach ($matches as $match) {
    			$stylesheetDeclaration = $match[0];
    			$stylesheetSrc = $match[1];
    			$stylesheetLink = LinkHelper::getUIChromeActionLink("uixul", "GetUICSS");
				$stylesheetLink->setQueryParameter('stylename', $stylesheetSrc);
				$stylesheetLink->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR);
				$xml = str_replace($stylesheetDeclaration, '<stylesheet src="' . $stylesheetLink->getUrl() . '" />', $xml);
		    }
		}
		if ( $request->hasParameter(K::WEBEDIT_MODULE_ACCESSOR) && $request->hasParameter(K::WIDGET_ACCESSOR) )
		{
			// Generate the binding method that initializes the available actions
			$setupActionsMethod = $this->generateSetupActionsMethod(
				$request->getParameter(K::WEBEDIT_MODULE_ACCESSOR),
				$request->getParameter(K::WIDGET_ACCESSOR)
				);
				
			if ( ! is_null($setupActionsMethod) )
			{
				$start = strpos($xml, '<method name="'.self::SETUP_ACTIONS_METHOD.'"');
				$end   = 0;
				if ( $start !== false )
				{
					$end = strpos($xml, "</method>", $start);
					if ( $end !== false )
					{
						$end += strlen("</method>");
						$xml = substr_replace($xml, $setupActionsMethod, $start, $end - $start);
					}
				}
				else
				{
					$xml = str_replace("</implementation>", "\n" . $setupActionsMethod . "\n</implementation>", $xml);
				}
			}

			// Generate the binding method that initializes the available columns
			$setupColumnsMethod = $this->generateSetupColumnsMethod(
				$request->getParameter(K::WEBEDIT_MODULE_ACCESSOR),
				$request->getParameter(K::WIDGET_ACCESSOR)
				);
			if ( ! is_null($setupColumnsMethod) )
			{
				$start = strpos($xml, '<method name="'.self::SETUP_COLUMNS_METHOD.'"');
				$end   = 0;
				if ( $start !== false )
				{
					$end = strpos($xml, "</method>", $start);
					if ( $end !== false )
					{
						$end += strlen("</method>");
						$xml = substr_replace($xml, $setupColumnsMethod, $start, $end - $start);
					}
				}
				else
				{
					$xml = str_replace("</implementation>", "\n" . $setupColumnsMethod . "\n</implementation>", $xml);
				}
			}

			// Generate the binding method that initializes the datasource
			$setupDatasourceMethod = $this->generateSetupDatasourceMethod(
				$request->getParameter(K::WEBEDIT_MODULE_ACCESSOR),
				$request->getParameter(K::WIDGET_ACCESSOR)
				);
			if ( ! is_null($setupDatasourceMethod) )
			{
				$start = strpos($xml, '<method name="'.self::SETUP_DATASOURCE_METHOD.'"');
				$end   = 0;
				if ( $start !== false )
				{
					$end = strpos($xml, "</method>", $start);
					if ( $end !== false )
					{
						$end += strlen("</method>");
						$xml = substr_replace($xml, $setupDatasourceMethod, $start, $end - $start);
					}
				}
				else
				{
					$xml = str_replace("</implementation>", "\n" . $setupDatasourceMethod . "\n</implementation>", $xml);
				}
			}
		}

		$this->setAttribute('contents', $xml);
		$rq->endI18nWork();
	}


	private function generateSetupActionsMethod($moduleName, $widgetId)
	{
		$defaultXmlElement = array();

		$widgetXmlFilePath = Resolver::getInstance('file')
					->setPackageName('modules_' . $moduleName)	// package name
					->setDirectory('config/widgets')			// path in the package
					->getPath($widgetId . '.xml');				// file

		// if the file exists and is readable, parse it with an XPATH query
		if ( is_readable($widgetXmlFilePath) )
		{
			$xml = f_object_XmlObject::getInstanceFromFile($widgetXmlFilePath);
			$xmlRoot = $xml->getRootElement();

			if (!isset($xmlRoot['do-not-use-default-events']))
			{
				$widgetsTagName = uixul_lib_UiService::getWidgetsInPerspective($moduleName);
				if (isset($widgetsTagName[$widgetId]))
				{
					$default = null;
					if (strpos($widgetsTagName[$widgetId], 'wtree') === 0)
					{
						$default = 'tree';
					}
					if (strpos($widgetsTagName[$widgetId], 'wlist') === 0)
					{
						$default = 'list';
					}
					
					if ($default)
					{
						$widgetXmlFilePath2 = Resolver::getInstance('file')
											->setPackageName('modules_uixul')		// package name
											->setDirectory('config/widgets')		// path in the package
											->getPath('default.'.$default.'.xml');	// file
						
						$xml2 = f_object_XmlObject::getInstanceFromFile($widgetXmlFilePath2);
						$xmlRoot2 = $xml2->getRootElement();
						$defaultXmlElement = $xmlRoot2->xpath("//event");
					}
				}
			}

			// query the XML document
			$xmlElement = array_merge($defaultXmlElement, $xmlRoot->xpath("//event"));

			$actionGroups = array();
			$actionsPerDoctype = array();

			for ($i=0 ; $i<count($xmlElement) ; $i++)
			{
				$docType   = strval($xmlElement[$i]['target']);
				$eventType = strval($xmlElement[$i]['type']);
				$actions   = split(' +', strval($xmlElement[$i]['actions']));

				$sourceType = null;

				if (strpos($eventType, ' ') === false)
				{
					$eventTypes = array($eventType);
				}
				else
				{
					$eventTypes = split(' +', $eventType);
				}
				
				if (strpos($docType, ' ') === false)
				{
					$docTypes = array($docType);
				}
				else
				{
					$docTypes = split(' +', $docType);
				}
				
				foreach ($docTypes as $docType)
				{
					foreach ($eventTypes as $eventType)
					{
						if ($eventType === 'drop')
						{
							$sourceType = strval($xmlElement[$i]['source']);
							if (strpos($sourceType, ' ') === false)
							{
								$sourceType = array( $sourceType );
							}
							else
							{
								$sourceType = split(' +', $sourceType);
							}
							$nbSourceType = count($sourceType);
							for ($j = 0 ; $j < $nbSourceType ; $j++)
							{
								$srcType = $sourceType[$j];
								$matches = null;
								if (preg_match('/^modules_([a-z]+)_([a-z]+)$/', $srcType, $matches))
								{
									try
									{
										$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($matches[1], $matches[2]);
										if ($documentModel->isInjectedModel())
										{
										    $sourceType[] = $documentModel->getBackofficeName();
										}
									}
									catch (Exception $e)
									{
										Framework::debug("No model injected for \"$srcType\".");
									}
								}
							}

							foreach ($sourceType as $srcType)
							{
								if (isset($actionsPerDoctype[$docType][$eventType][$srcType]))
								{
									$actionsPerDoctype[$docType][$eventType][$srcType] = array_merge($actionsPerDoctype[$docType][$eventType][$srcType], $actions);
								}
								else
								{
									$actionsPerDoctype[$docType][$eventType][$srcType] = $actions;
								}
							}
						}
						else
						{
							// intbonjf 2006-05-18:
							// if actions have already been defined for this
							// document type, merge the found actions
							// with the one already defined.
							if (isset($actionsPerDoctype[$docType][$eventType]) && is_array($actionsPerDoctype[$docType][$eventType]))
							{
								$actionsPerDoctype[$docType][$eventType] = array_merge($actionsPerDoctype[$docType][$eventType], $actions);
							}
							else
							{
								$actionsPerDoctype[$docType][$eventType] = $actions;
							}

							// add the workflow handling actions only in the toolbar
							if ($eventType == 'select')
							{
								$matches = null;
								if (preg_match('/^modules_([a-z]+)_([a-z]+)$/', $docType, $matches))
								{
									try
									{
										$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($matches[1], $matches[2]);
										$actionsPerDoctype[$docType][$eventType][] = '|';
										if ($documentModel->getDefaultNewInstanceStatus() == 'DRAFT')
										{
											if ($documentModel->hasWorkflow())
											{
												$actionsPerDoctype[$docType][$eventType][] = 'startValidation';
											}
											else
											{
												$actionsPerDoctype[$docType][$eventType][] = 'activate';
											}
										}
									}
									catch (Exception $e)
									{
										Framework::warn(__METHOD__.": unknown document model: ".$e->getMessage());
									}
								}
							}
						}
					}
				}

				// look for actiongroups
				$actionGroupElements = $xmlElement[$i]->xpath("actiongroup[attribute::name][attribute::actions]");
				foreach ($actionGroupElements as $actionGroupElement)
				{
					$name  = strval($actionGroupElement['name']);
					$label = strval($actionGroupElement['label']);
					$icon  = strval($actionGroupElement['icon']);
					if (empty($icon))
					{
						$icon = "gear";
					}
					if (empty($label))
					{
						$label = "&modules.$moduleName.bo.actions.".ucfirst($name).";";
					}
					elseif (!f_Locale::isLocaleKey($label))
					{
						$label = "&modules.$moduleName.bo.actions.".ucfirst($label).";";
					}
					$label = f_Locale::translate($label);
					$icon  = MediaHelper::getIcon($icon, MediaHelper::SMALL);
					$actionGroups[] = array(
						"name"   => $name,
						"label"   => $label,
						"icon"    => $icon,
						"actions" => split(' +', strval($actionGroupElement['actions']))
						);
				}
			}

			$xml = array(
				'<method name="'.self::SETUP_ACTIONS_METHOD.'">',
				'<body><![CDATA['
				);

			// document injection --
			$docTypeArray = array_keys($actionsPerDoctype);
			foreach ($docTypeArray as $docType)
			{
				if (preg_match('/^modules_([a-z]+)_([a-z]+)$/', $docType, $matches))
				{
					try
					{
						$model = f_persistentdocument_PersistentDocumentModel::getInstance($matches[1], $matches[2]);
						if ($model->isInjectedModel())
						{
           				    $actionsPerDoctype[$model->getBackofficeName()] = $actionsPerDoctype[$docType];
						}
					}
					catch (Exception $e)
					{
						continue;
					}

				}
			}
			// -- document injection

			foreach ($actionsPerDoctype as $docType => $actionsPerEventType)
			{
				$xml[] = sprintf('this._availableActions["%s"] = {', $docType);
				foreach ($actionsPerEventType as $eventType => $actions)
				{
					if ($eventType == 'drop')
					{
						$xml[] = $eventType . " : { ";
						foreach ($actions as $srcType => $srcActions)
						{
							// intbonjf 2006-07-20:
							// add actions that are available for all components
							$srcActions = array_merge($actionsPerDoctype[$docType][$eventType][$srcType], $srcActions);
							// intbonjf 2006-07-20:
							// Manage "negative actions": if an action is prefixed with
							// a dash, it will be removed from the list of available
							// actions.
							$srcActions = self :: cleanupActionsList($srcActions);
							$xml[] = $srcType . " : " . sprintf('[ "%s" ],', join('", "', $srcActions));
						}
						$xml[] = '},';
					}
					else
					{
						// intbonjf 2006-07-20:
						// add actions that are available for all components
						$actions = array_merge($actionsPerDoctype["*"][$eventType], $actions);
						// intbonjf 2006-07-20:
						// Manage "negative actions": if an action is prefixed with
						// a dash, it will be removed from the list of available
						// actions.
						$actions = self :: cleanupActionsList($actions);
						$xml[] = $eventType . " : " . sprintf('[ "%s" ],', join('", "', $actions));
					}
				}
				$xml[] = "};";
			}

			foreach ($actionGroups as $actionGroup)
			{
				$xml[] = sprintf(
					'this._availableActionGroups["%s"] = { label:"%s", icon:"%s", actions:["%s"] }',
					$actionGroup['name'], $actionGroup['label'], $actionGroup['icon'], join('", "', $actionGroup['actions'])/*, $actionGroup['toggle']*/);
			}

			$xml[] = ']]></body>';
			$xml[] = '</method>';

			return join(K::CRLF, $xml);
		}

		return null;
	}


	private static function cleanupActionsList($in_actions)
	{
	    $actionHistory = array();

	    foreach ($in_actions as $actionName)
		{
			if ($actionName && strlen($actionName) > 1 && $actionName{0} === '-')
			{
			    $actionHistory[] = $actionName;
				$actionHistory[] = substr($actionName, 1);
			}
		}

		$out_actions = array();

		$lastActionName = '';

		foreach ($in_actions as $actionName)
		{
		    if (!in_array($actionName, $actionHistory))
		    {
		        if (strlen($actionName) > 1)
		        {
		            $actionHistory[] = $actionName;
		        }

		        if ($actionName != $lastActionName)
		        {
		            $out_actions[] = $actionName;
		        }
		    }
		    $lastActionName = $actionName;
		}

		return $out_actions;
	}


	private function generateSetupColumnsMethod($moduleName, $widgetId)
	{
		$widgetXmlFilePath = Resolver::getInstance('file')
							->setPackageName('modules_' . $moduleName)	// package name
							->setDirectory('config/widgets')			// path in the package
							->getPath($widgetId . '.xml');				// file

		if ( is_null($widgetXmlFilePath))
		{
			$widgetXmlFilePath = Resolver::getInstance('file')
								->setPackageName('modules_uixul')	// package name
								->setDirectory('config/widgets')	// path in the package
								->getPath($widgetId . '.xml');		// file
		}

		// if the file exists and is readable, parse it with an XPATH query
		if ( is_readable($widgetXmlFilePath) )
		{
			$xml = f_object_XmlObject::getInstanceFromFile($widgetXmlFilePath);
			$xmlRoot = $xml->getRootElement();

			// query the XML document
			$xmlElement = $xmlRoot->xpath("//columns");

			$contents = array(
				'<method name="'.self::SETUP_COLUMNS_METHOD.'">',
				'<body><![CDATA['
				);

			// See modules/uixul/lib/bindings/widgets/wList.xml
			$forbiddenColumnNames = array("id", "lang", "parentid", "type", "status", "revision", "rights");

			// for each element that matches the conditions
			for ($i=0 ; $i<count($xmlElement) ; $i++)
			{
				if (!isset($xmlElement[$i]['for-parent-type']))
				{
					$parent = 'default';
				}
				else
				{
					$parent = strval($xmlElement[$i]['for-parent-type']);
				}
				$columnsElm = $xmlElement[$i]->xpath('column');
				$columnObjects = array();
				for ($j=0 ; $j<count($columnsElm) ; $j++)
				{
					$column = $columnsElm[$j];
					$colAttributes = array();
					$labelOK = false;
					$attrs = $column->attributes();
					foreach ($attrs as $name => $value)
					{
						$value = strval($value);
						// I must check if the given ID is in the fobidden column names
						if ($name === "ref" && in_array($value, $forbiddenColumnNames))
						{
							$newValue = "your-" . $value;
							$contents[] = sprintf("throw new Error(\"Column '%s' cannot be redefined in widget '%s' in module '%s': it has been renamed to '%s'. Please rename it (forbidden names are: '%s').\");", $value, $widgetId, $moduleName, $newValue, join("', '", $forbiddenColumnNames));
							$value = $newValue;
						}

						if ($name === "label")
						{
							if (!f_Locale::isLocaleKey($value))
							{
								$value = '&modules.'.$moduleName.'.bo.widgets.'.$widgetId.'.'.ucfirst($value).';';
							}
							$colAttributes[] = $name . ': "' . f_Locale::translate($value) . '"';
							$labelOK = true;
						}
						else
						{
							$colAttributes[] = $name . ': "' . $value . '"';
						}
					}
					if (!$labelOK)
					{
						$value = '&modules.'.$moduleName.'.bo.widgets.'.$widgetId.'.'.ucfirst($attrs['ref']).';';
						$colAttributes[] = 'label: "' . f_Locale::translate($value) . '"';
					}
					$columnObjects[] = '{' . join(", ", $colAttributes) . '}';
				}
				$parentArray = split(' +', $parent);
				foreach ($parentArray as $parent)
				{
					if ('default' == $parent)
					{
						$contents[] = sprintf("this._availableColumns['%s'] = [ %s ];", $parent, join(", ", $columnObjects));
					}
					else if (preg_match('/^modules_([a-z]+)_([a-z]+)$/', $parent, $matches))
					{
						try
						{
							$documentModel = f_persistentdocument_PersistentDocumentModel::getInstance($matches[1], $matches[2]);
							if ($documentModel->isInjectedModel())
							{
								$parent = $documentModel->getBackofficeName();
							}
						}
						catch (Exception $e)
						{
							Framework::debug("No model injected for \"$parent\".");
						}
						$contents[] = sprintf("this._availableColumns['%s'] = [ %s ];", $parent, join(", ", $columnObjects));
					}
				}
			}

			$contents[] = ']]></body>';
			$contents[] = '</method>';

			return join(K::CRLF, $contents);
		}

		return null;
	}


	private function generateSetupDatasourceMethod($moduleName, $widgetId)
	{
		$widgetXmlFilePath = Resolver::getInstance('file')
							->setPackageName('modules_' . $moduleName)	// package name
							->setDirectory('config/widgets')			// path in the package
							->getPath($widgetId . '.xml');				// file

		$contents = array(
			'<method name="'.self::SETUP_DATASOURCE_METHOD.'">',
			'<body><![CDATA['
			);
		// if the file exists and is readable, parse it with an XPATH query
		if ( is_readable($widgetXmlFilePath) )
		{			
			$xml = f_object_XmlObject::getInstanceFromFile($widgetXmlFilePath);
			$xmlRoot = $xml->getRootElement();

			// query the XML document for datasource elements :
			$datasourceElements = $xmlRoot->xpath("/behaviour/datasource");

			if (count($datasourceElements) == 1)
			{
				
				$datasourceElement = $datasourceElements[0];
				$ref = 'http://wc';

				$jsDataSource = array();
				foreach ($datasourceElement->attributes() as $name => $value)
				{
					if ( $name === "root" )
					{
						$value = $ref . '/' . $value;
					}
					// intportg - 2008-10-20 - Add injected models to the allowed components.
					else if ( $name === "components" )
					{
						$originalModelNames = explode(',', $value);
						$modelNames = $originalModelNames;
						
						$datasourceElements = $datasourceElement->attributes();
						$module = $datasourceElements['module'];
						foreach ($originalModelNames as $originalModelName)
						{
							// We need the complete model name, not the short one.
							if (substr($originalModelName, 0, 8) != 'modules_')
							{
								$originalModelName = 'modules_'.$module.'/'.$originalModelName;
							}							
							$modelNames = $this->addInjectedModelToArray($originalModelName, $modelNames);
						}
						$value = implode(',', $modelNames);
					}					
					$jsDataSource[] = $name.': "'.$value.'"';
				}
				$contents[] = sprintf("this._dataSource = { %s };", join(", ", $jsDataSource));
			}
			else
			{				
				// query the XML document for datasourceS elements (used by multitree) :
				$datasourcesElements = $xmlRoot->xpath("/behaviour/datasources");
				
				if (count($datasourcesElements) == 1)
				{					
					$datasourcesElements = $datasourcesElements[0];

					$pushedDsContent = array();
					switch (strval($datasourcesElements['import']))
					{
						case 'picker' :
							$linkedModuleArray = ModuleService::getInstance()->getLinkedModules($moduleName);
							foreach ($linkedModuleArray as $linkedModuleName)
							{								
								$dsArray = $this->getDatasources($linkedModuleName);
								foreach ($dsArray as $ds)
								{
									if ($this->canDisplayModuleAsDatasource($ds['module']))
									{
										$pushedDsContent[$this->getDatasourceLabel($ds)] = $this->generateJavascriptDatasources($ds);
									}
								}
							}
							break;

						case 'pagecontent' :							
							$linkedModuleArray = ModuleService::getInstance()->getModules();
							foreach ($linkedModuleArray as $linkedModuleName)
							{								
								if (strpos($linkedModuleName, 'modules_') === 0)
								{
									$linkedModuleName = substr($linkedModuleName, 8);
									$dsArray = $this->getDatasources($linkedModuleName, 'pagecontent');
									foreach ($dsArray as $ds)
									{
										if ($this->canDisplayModuleAsDatasource($ds['module']))
										{
											$pushedDsContent[$this->getDatasourceLabel($ds)] = $this->generateJavascriptDatasources($ds);
										}
									}
								}
							}
							break;
					}

					foreach ($datasourcesElements->datasource as $datasourceElement)
					{						
						$ds = array();
						foreach ($datasourceElement->attributes() as $name => $value)
						{
							$ds[$name] = strval($value);
						}
						if ($this->canDisplayModuleAsDatasource($ds['module']))
						{
							$pushedDsContent[$this->getDatasourceLabel($ds)] = $this->generateJavascriptDatasources($ds);
						}
					}
					foreach ($datasourcesElements->module as $linkedModule)
					{						
						$linkedModuleName = strval($linkedModule['name']);
						$dsName = trim(strval($linkedModule));
						$dsArray = $this->getDatasources($linkedModuleName, $dsName);
						foreach ($dsArray as $ds)
						{
							if (isset($linkedModule['label']))
							{
								$ds['label'] = strval($linkedModule['label']);
							}
							if ($this->canDisplayModuleAsDatasource($ds['module']))
							{
								$pushedDsContent[$this->getDatasourceLabel($ds)] = $this->generateJavascriptDatasources($ds);
							}
						}
					}

					ksort($pushedDsContent, SORT_STRING);

					foreach ($pushedDsContent as $dsJsContent)
					{
					    $contents[] = $dsJsContent;
					}
				}
			}
		}

		$contents[] = ']]></body>';
		$contents[] = '</method>';

		return join(K::CRLF, $contents);
	}

	/**
	 * @param String $moduleName
	 * @return Boolean
	 */
	private function canDisplayModuleAsDatasource($moduleName)
	{
		$ps = f_permission_PermissionService::getInstance();
		return $ps->hasPermission(users_UserService::getInstance()->getCurrentBackEndUser(), 'modules_' . $moduleName  . '.List.rootfolder', ModuleService::getInstance()->getRootFolderId($moduleName));
	}	

	/**
	 * @param array $datasource
	 */
	private function generateJavascriptDatasources($datasource)
	{
		$jsDataSource = array();

		foreach ($datasource as $name => $value)
		{
			switch ($name)
			{
				case 'label' :
					$value = $this->getDatasourceLabel($datasource);
					break;

				case 'icon' :
					$value = MediaHelper::getIcon($value, MediaHelper::SMALL);
					break;
			}

			$jsDataSource[] = $name.': "'.$value.'"';
		}

		return sprintf("this._dataSources.push({ %s });", join(", ", $jsDataSource));
	}


	/**
	 * @param array $datasource
	 */
	private function getDatasourceLabel($datasource)
	{
	    if (isset($datasource['module']))
	    {
	        $localizedModuleName = f_Locale::translateUI("&modules." . $datasource['module'] . ".bo.general.Module-name;");
	    }
	    else
	    {
	        $localizedModuleName = '';
	    }

	    if (isset($datasource['label']))
	    {
	        $label = f_Locale::translateUI($datasource['label']);
	    }
	    else
	    {
	        $label = $localizedModuleName;
	    }

	    if ($label != $localizedModuleName)
		{
			$label = sprintf('%s - %s', $localizedModuleName, $label);
		}

		return $label;
	}


	private function getDatasources($moduleName, $datasourceName = null)
	{
		$file = FileResolver::getInstance()->setPackageName('modules_'.$moduleName)
			->setDirectory('config')->getPath('datasources.xml');
		$datasources = array();
		if ( ! is_null($file) )
		{
			$domDoc = new DOMDocument();
			$domDoc->load($file);
			$xpath = new DOMXPath($domDoc);
			if (is_null($datasourceName) || $datasourceName == '')
			{
				$query = '/datasources/datasource[not(@name)]';
			}
			else
			{
				$query = '/datasources/datasource[@name="'.$datasourceName.'" or @type="'.$datasourceName.'"]';
			}
			$nodeList = $xpath->query($query);
			for ($i=0 ; $i<$nodeList->length ; $i++)
			{
				$datasourceElm = $nodeList->item($i);
				$result = array('module' => $moduleName);
				$attributes = array('treecomponents', 'listcomponents', 'label', 'icon', 'listfilter', 'listparser', 'treeparser');
				foreach ($attributes as $attribute)
				{
					if ($datasourceElm->hasAttribute($attribute))
					{
						switch ($attribute)
						{
							case 'treecomponents' :
							case 'listcomponents' :
								$originalModelNames = explode(',', $datasourceElm->getAttribute($attribute));
								$modelNames = $originalModelNames;

								foreach ($originalModelNames as $originalModelName)
								{
									// INTCOURS - Add injected models to the datasource :
									$modelNames = $this->addInjectedModelToArray($originalModelName, $modelNames);
								}

								$result[$attribute] = implode(',', $modelNames);
								break;
							default:
								$result[$attribute] = $datasourceElm->getAttribute($attribute);
								break;
						}
					}
					else
					{
						switch ($attribute)
						{
							case 'treecomponents' :
							case 'listcomponents' :
								$result[$attribute] = '*';
								break;

							case 'label' :
								$result[$attribute] = "&modules.$moduleName.bo.general.Module-name;";
								break;

							case 'icon' :
								$result[$attribute] = constant('MOD_'.strtoupper($moduleName).'_ICON');
								break;
						}
					}
				}
				$datasources[] = $result;
			}
		} 
		else if (uixul_ModuleBindingService::getInstance()->hasConfigFile($moduleName))
		{
			$config = uixul_ModuleBindingService::getInstance()->loadConfig($moduleName);
			$treecomponents = array();
			$listcomponents = array();
			foreach ($config['models'] as $name => $modelInfo) 
			{
				if (isset($modelInfo['children']))
				{		
					$treecomponents[] = $name;
				}
				else
				{
					$listcomponents[] = $name;
				}
			}
			$result['treecomponents'] = implode(',', $treecomponents);
			if (count($listcomponents) == 0) {$listcomponents = $treecomponents;};
			$result['listcomponents'] = implode(',', $listcomponents);
			$result['label'] = "&modules.$moduleName.bo.general.Module-name;";
			$result['icon'] = constant('MOD_'.strtoupper($moduleName).'_ICON');
			$result['module'] = $moduleName;
			$datasources[] = $result;
		}
		return $datasources;
	}

	/**
	 * @param String $completeModelName
	 * @param Array<String> $modelNames
	 * @return Array<String> the completed Array.
	 */
	private function addInjectedModelToArray($completeModelName, $modelNames)
	{
		try
		{
			if (f_persistentdocument_PersistentDocumentModel::exists($completeModelName))
			{
				$documentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($completeModelName);
				if ($documentModel->isInjectedModel())
				{
					$modelNames[] = $documentModel->getName();
				}
			}
			else
			{
				Framework::info(__METHOD__.": ignored documentmodel ".$completeModelName);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return $modelNames;
	}
}
