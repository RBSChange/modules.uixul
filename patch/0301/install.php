<?php
/**
 * uixul_patch_0301
 * @package modules.uixul
 */
class uixul_patch_0301 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$filePath = f_util_FileUtils::buildCachePath('cleanXHTMLFragment.xsl');
		@unlink($filePath);
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'uixul';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0301';
	}
}