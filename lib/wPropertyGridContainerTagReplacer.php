<?php
class uixul_lib_wPropertyGridContainerTagReplacer extends f_util_TagReplacer
{
	protected function preRun()
	{
		$pGrids = array();
		$bs = block_BlockService::getInstance();
		foreach ($bs->getBlocksWithPropertyGrid() as $block)
		{
			$pGrids[] = '"'.$block.'"';
		}
		$this->setReplacement('PROPERTYGRIDS', '[' . implode( ',' , $pGrids ) . ']' );
	}
}
