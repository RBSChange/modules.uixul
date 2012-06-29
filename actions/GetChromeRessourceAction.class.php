<?php
/**
 */
class uixul_GetChromeRessourceAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		
		$path = $request->getParameter('path');
		$filename = f_util_FileUtils::buildDocumentRootPath($path);
		if (is_readable($filename))
		{
			header("Expires: " . gmdate("D, d M Y H:i:s", time()+28800) . " GMT");
			if (f_util_FileUtils::getFileExtension($filename) == 'css')
			{
				header('Content-type: text/css');
			}
			else
			{
				$finfo = finfo_open(FILEINFO_MIME, Framework::getConfigurationValue("modules/media/fileinfo_magic_file_path", "/usr/share/file/magic")); // return mime type ala mimetype extension
				header('Content-type: ' . finfo_file($finfo, $filename));
				finfo_close($finfo);
			}
			readfile($filename);			
		}
		else
		{
			f_web_http_Header::setStatus(404);
		}
		return change_View::NONE;
	}
}
