<?php
/**
 * uixul_patch_0300
 * @package modules.uixul
 */
class uixul_patch_0300 extends patch_BasePatch
{
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
	public function isCodePatch()
	{
		return true;
	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$result = array();
		exec('bash framework/bin/rename.sh layout' . '.wBlockToolbar layout.cLayoutToolbars modules/*', $result);
		foreach ($result as $string) 
		{
			$this->log($string);
		}
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
		return '0300';
	}
}