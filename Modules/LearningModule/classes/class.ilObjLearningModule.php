<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Modules/LearningModule/classes/class.ilObjContentObject.php");

/**
* Class ilObjLearningModule
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilObjLearningModule.php 29794 2011-07-07 09:36:58Z akill $
*
* @ingroup ModulesIliasLearningModule
*/
class ilObjLearningModule extends ilObjContentObject
{

	/**
	* Constructor
	* @access	public
	*/
	function ilObjLearningModule($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "lm";
		parent::ilObjContentObject($a_id, $a_call_by_reference);
	}

}

?>
