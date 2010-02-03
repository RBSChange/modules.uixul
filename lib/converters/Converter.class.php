<?php
interface uixul_Converter
{
	/**
	 * Converts the file $file and returns the converted contents as a string.
	 *
	 * @param string $file
	 * @return string
	 */
	public function convert($file);
}