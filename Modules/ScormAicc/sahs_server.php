<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/**
* scorm learning module presentation script
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: sahs_server.php 35580 2012-07-17 06:56:35Z ukohnle $
*
*/

chdir("../..");
require_once "./Modules/ScormAicc/classes/class.ilObjSAHSLearningModule.php";

$cmd = ($_GET["cmd"] == "")
	? $_POST["cmd"]
	: $_GET["cmd"];

$ref_id=$_GET["ref_id"];

//get type of cbt
if (!empty($ref_id))
{
	require_once "./include/inc.header.php";

	$obj_id = ilObject::_lookupObjectId($ref_id);
	$type = ilObjSAHSLearningModule::_lookupSubType($obj_id);

}
else
{

	//ensure HACP
	$requiredKeys=array("command", "version", "session_id");
	if (count(array_diff ($requiredKeys, array_keys(array_change_key_case($_POST, CASE_LOWER))))==0)
	{
		//now we need to get a connection to the database and global params
		//but that doesnt work because of missing logindata of the contentserver
		//require_once "./include/inc.header.php";

		//highly insecure
		$param=urldecode($_POST["session_id"]);
		if (!empty($param) && substr_count($param, "_")==3)
		{
			list($session_id, $client_id, $ref_id, $obj_id)=explode("_",$param);
			
			$_COOKIE[session_name()] = $session_id;
			$_COOKIE['ilClientId'] = $client_id;

//			session_id($session_id);
			require_once "./include/inc.header.php";
//$ilLog->write("Session: ".$_POST["session_id"]);

			$type="hacp";

		}
	}
}

switch ($type)
{
	case "scorm":
				//SCORM
				require_once "./Modules/ScormAicc/classes/SCORM/class.ilObjSCORMTracking.php";
				$track = new ilObjSCORMTracking();
				$track->$cmd();
				break;
	case "aicc":
				//AICC
				require_once "./Modules/ScormAicc/classes/AICC/class.ilObjAICCTracking.php";
				$track = new ilObjAICCTracking();
				$track->$cmd();
				break;
	case "hacp":
				//HACP
				require_once "./Modules/ScormAicc/classes/HACP/class.ilObjHACPTracking.php";
				$track = new ilObjHACPTracking($ref_id, $obj_id);
				//$track->$cmd();
				break;
	default:
				//unknown type
				$GLOBALS['ilLog']->write('sahs_server.php: unknown type >'.$type.'<');
}

exit;

?>
