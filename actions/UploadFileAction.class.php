<?php

class uixul_UploadFileAction extends f_action_BaseJSONAction
{
    const FILENAME = 'filename';

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
		try
		{
			$this->getTransactionManager()->beginTransaction();
			
    		if (!$request->hasFile(self::FILENAME))
    		{
               throw new IOException('no-file'); 
    		}
    		
		    if ($request->hasFileError(self::FILENAME))
		    {
		        switch ($request->getFileError(self::FILENAME))
		        {
		            case UPLOAD_ERR_INI_SIZE:
		                throw new ValidationException('ini-size');
		                break;

		            case UPLOAD_ERR_FORM_SIZE:
		                throw new ValidationException('form-size');
		                break;

		            case UPLOAD_ERR_PARTIAL:
		                throw new IOException('partial-file');
		                break;

		            case UPLOAD_ERR_NO_FILE:
		                throw new IOException('no-file');
		                break;

		            case UPLOAD_ERR_NO_TMP_DIR:
                    case UPLOAD_ERR_CANT_WRITE:
		                throw new IOException('cannot-write');
		                break;

            	    default:
		                throw new IOException('unknown');
		                break;
		        }
		    }

		    $fileName = $request->getFileName(self::FILENAME);
		    $filePath = $request->getFilePath(self::FILENAME);
		    $fileExtension = f_util_FileUtils::getFileExtension($fileName, true);
		    $cleanFileName = basename($fileName, $fileExtension);

		    if (!is_uploaded_file($filePath))
			{
			    throw new IOException('no-file');
			}
			
			$tmpFileName = f_util_FileUtils::getTmpFile('UploadFile_');
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' filePath:' . $filePath);
				Framework::debug(__METHOD__ . ' tmpFileName:' . $tmpFileName);
			}
			
		    try
    		{
    		    if (!$request->moveFile(self::FILENAME, $tmpFileName))
    		    {
    		        throw new IOException('cannot-move');
    		    }
    		}
    		catch (Exception $e)
    		{
    		    Framework::exception($e);
    		    throw new IOException('cannot-move', null, $e);
    		}   			
            
    		
    		$mediaId = intval($this->getDocumentIdFromRequest($request));
    		
    		$media = media_TmpfileService::getInstance()->getNewDocumentInstance();
    		
    		$label = $request->getParameter('label');
    		if (f_util_StringUtils::isEmpty($label))
    		{
    			$label = '';
    			$media->setLabel(f_util_StringUtils::utf8Encode($cleanFileName));
    		}
    		else
    		{
    			$media->setLabel($label);
    		}
    		
 
    		if ($mediaId > 0)
    		{
    			$media->setOriginalfileid($mediaId);
    		}        
        	$media->setNewFileName($tmpFileName, f_util_StringUtils::utf8Encode($fileName));
        	$media->save();
        	
        	$mediaFolderName = $request->getParameter('mediafoldername'); 
        	if (f_util_StringUtils::isNotEmpty($mediaFolderName))
        	{
        		$media = $this->appendToMedia($media, $mediaFolderName, $label);	
        	}
    		$mediaId =  $media->getId();
    		$mediaLabel = $media->getLabel();
    		$lang = RequestContext::getInstance()->getLang();
    		
    		$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			return $this->sendJSONException($e);
		}
		$result = array('id' => $mediaId, 'lang' => $lang, 
			'labels' => array($lang => $mediaLabel),
			'infos' => $media->getInfo()	
		);
		return $this->sendJSON($result);
	}

	public function getRequestMethods()
	{
		return Request::POST;
	}
	
	/**
	 * @param media_persistentdocument_tmpfile $tmpFile
	 * @param string $mediaFolderName
	 * @param string $label
	 * @return media_persistentdocument_media
	 */
	private function appendToMedia($tmpFile, $mediaFolderName, $label = '')
	{
		Framework::warn(__METHOD__ . ' ' . $mediaFolderName . ' ' . $tmpFile->__toString());
		$media = media_MediaService::getInstance()->importFromTempFile($tmpFile);
		
		Framework::warn(__METHOD__ . ' MEDIA ' . $media->__toString());
		if ($label != '')
		{
			$media->setTitle($label);
		}
			
		if ($media->isNew())
		{
			
			$rootFolderId = ModuleService::getInstance()->getRootFolderId('media');
			Framework::warn(__METHOD__ . ' ROOTFOLDER ' . $rootFolderId);
			
			$ts = TreeService::getInstance();	
			$rootNode = $ts->getRootNode($rootFolderId);
			
			$folders = generic_FolderService::getInstance()->createQuery()
				->add(Restrictions::eq('label', $mediaFolderName))
				->add(Restrictions::childOf($rootFolderId))->find();
				
			if (count($folders) == 0)
			{
				$folder = generic_FolderService::getInstance()->getNewDocumentInstance();
				$folder->setLabel($mediaFolderName);
				$folder->save();	
				$ts->newChildAtForNode($rootNode, $folder->getId());	
			}
			else
			{
				$folder = $folders[0];
			}
			Framework::warn(__METHOD__ . ' FOLDER ' . $folder->__toString());
			
			$media->save($folder->getId());
		}
		else
		{
			$media->save();
		}	
		return $media;
	}
}