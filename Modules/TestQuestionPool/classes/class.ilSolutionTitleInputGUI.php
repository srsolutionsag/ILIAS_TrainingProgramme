<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* This class represents a custom property in a property form.
*
* @author Helmut Schottmüller <ilias@aurealis.de> 
* @version $Id: class.ilSolutionTitleInputGUI.php 44245 2013-08-17 11:15:45Z mbecker $
* @ingroup	ServicesForm
*/
class ilSolutionTitleInputGUI extends ilCustomInputGUI
{
	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		return true;
	}
}
