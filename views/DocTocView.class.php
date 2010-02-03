<?php
class uixul_DocTocView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->parseDirectory(dirname(__FILE__) . '/../lib/bindings');
	}
	
	private function parseDirectory($directory)
	{
		$dirObject = dir($directory);
		while ($entry = $dirObject->read())
		{
			$path = $directory . DIRECTORY_SEPARATOR . $entry;
			if ($entry{0} != '.' && $entry !== 'locale')
			{
				if (is_dir($path))
				{
					echo '<h2>' . $entry . '</h2>';
					$this->parseDirectory($path);
				}
				else if (substr($entry, -4) === '.xml')
				{
					$entry = substr($entry, 0, -4);
					echo '<a href="/xul_controller.php?module=uixul&action=Doc&binding='.basename($directory).'.'.$entry.'">' . $entry . '</a><br />';
				}
			}
		}		
	}
}