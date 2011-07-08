<?php

class uixul_lib_DocumentEditorPanelTagReplacer extends f_util_TagReplacer
{
	protected $panelName;
	protected $moduleName;
	protected $documentModelName;
	
	protected static $systemproperties; 
	
	/**
	 * @param string $panelName
	 * @param string $moduleName
	 * @param string $documentModelName
	 * @return f_util_TagReplacer
	 */
	public static function getInstance($panelName, $moduleName, $documentModelName)
	{
		if (self::$systemproperties === null)
		{
			self::$systemproperties = array(
				'id' => true, 
				'model' => true, 
				'author' => true, 
				'authorid' => true, 
				'creationdate' => true, 
				'modificationdate' => true, 
				'publicationstatus' => true, 
				'lang' => true,
				'metastring' => true,  
				'modelversion' => true, 
				'documentversion' => true,
				'correctionid' => true
			);
		}
		if ($panelName === 'permission')
		{
			return new uixul_lib_DocumentEditorPanelPermissionTagReplacer($panelName, $moduleName, $documentModelName);
		}
		
		return new uixul_lib_DocumentEditorPanelTagReplacer($panelName, $moduleName, $documentModelName);
	}
	
	protected function __construct($panelName, $moduleName, $documentModelName)
	{
		$this->panelName = $panelName;
		$this->moduleName = $moduleName;
		$this->documentModelName = $documentModelName;
	}
	
	protected function preRun()
	{
		$fields = array();
		$localizedFields = array();
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->documentModelName);
		$properties = $model->getEditablePropertiesInfos();
		$pubondaychange = $model->publishOnDayChange();
		foreach ($properties as $name => $property) 
		{
			if (isset(self::$systemproperties[$name])) {continue;}
			if (!$pubondaychange && ($name === 'startpublicationdate' || $name === 'endpublicationdate')) {continue;}
			$fields[] = '<field name="' . $name . '" />';
			if ($property->isLocalized())
			{
				$localizedFields[] = '<field name="' . $name . '" />';
			}
		}
		$this->setReplacement('FIELDS', implode("\n", $fields));	
		$this->setReplacement('LOCALIZEDFIELDS', implode("\n", $localizedFields));
		
		$sections = array();
		$rc = RequestContext::getInstance();
		$langs = $rc->getSupportedLanguages();
		foreach ($langs as $lang) 
		{
			$sections[] = '<section name="' . $lang .'" />';
		}
		$this->setReplacement('PUBLICATIONSECTION', implode("\n", $sections));	
	}
}

class uixul_lib_DocumentEditorPanelPermissionTagReplacer extends uixul_lib_DocumentEditorPanelTagReplacer
{
	protected function preRun()
	{
		$backFields = array();
		$frontFields = array();
		$securityGenerator  = new builder_SecurityGenerator();
		$roles = $securityGenerator->getRolesFields($this->moduleName);
		
		list( , $documentName) = explode('/', $this->documentModelName);
		foreach ($roles as $name => $info) 
		{
			$id = $this->moduleName .'_'.$documentName. '_perm_' . $name;
			$labeli18n = "modules.".$this->moduleName.".document.permission.".ucfirst($name);
			$field = '<field name="' . $name . '" hideorder="true" rows="3" editwidth="350" type="documentarray" allow="'.$info['class'].'"  id="'.$id.'" anonid="field_'.$name.'" moduleselector="users" labeli18n="'.$labeli18n.'" />';
			if ($info['type'] == 'back')
			{
				$backFields[] = $field;
			}
			else 
			{
				$frontFields[] = $field;
			}
		}
		$this->setReplacement('BACK_FIELDS', implode("\n", $backFields));
		$this->setReplacement('FRONT_FIELDS', implode("\n", $frontFields));
	}
}