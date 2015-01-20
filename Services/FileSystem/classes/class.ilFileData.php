<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* This class handles all operations on files in directory /ilias_data/
*  
* @author	Stefan Meyer <meyer@leifos.com>
* @version $Id: class.ilFileData.php 33510 2012-03-04 11:59:59Z akill $
* 
*/
require_once("./Services/FileSystem/classes/class.ilFile.php");

class ilFileData extends ilFile
{

	/**
	* Constructor
	* class bas constructor and read path of directory from ilias.ini
	* setup an mail object
	* @access	public
	*/
	function ilFileData()
	{
		parent::ilFile();
		$this->path = CLIENT_DATA_DIR;
	}

	/**
	* check if path exists and is writable
	* @param string path to check
	* @access	public
	* @return bool
	*/
	function checkPath($a_path)
	{
		if(is_writable($a_path))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* get Path 
	* @access	public
	* @return string path
	*/
	function getPath()
	{
		return $this->path;
	}
}
?>