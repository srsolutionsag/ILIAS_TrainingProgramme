<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Component/classes/class.ilModule.php");

/**
* TestQuestionPool Module.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilTestQuestionPoolModule.php 44245 2013-08-17 11:15:45Z mbecker $
*
* @ingroup ModulesTestQuestionPool
*/
class ilTestQuestionPoolModule extends ilModule
{
	
	/**
	* Constructor: read information on component
	*/
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	* Core modules vs. plugged in modules
	*/
	function isCore()
	{
		return true;
	}

	/**
	* Get version of module. This is especially important for
	* non-core modules.
	*/
	function getVersion()
	{
		return "-";
	}

}
?>
