<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "./Services/Object/classes/class.ilObject.php";
require_once "./Modules/ScormAicc/classes/class.ilObjSCORMValidator.php";
require_once "./Modules/ScormAicc/classes/class.ilObjSAHSLearningModule.php";

/**
* Class ilObjSCORMLearningModule
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id: class.ilObjSCORMLearningModule.php 56954 2015-01-09 12:46:22Z ukohnle $
*
* @ingroup ModulesScormAicc
*/
class ilObjSCORMLearningModule extends ilObjSAHSLearningModule
{
	var $validator;

	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjSCORMLearningModule($a_id = 0, $a_call_by_reference = true)
	{
		$this->type = "sahs";
		parent::ilObject($a_id,$a_call_by_reference);
	}


	/**
	* Validate all XML-Files in a SCOM-Directory
	*
	* @access       public
	* @return       boolean true if all XML-Files are wellfomred and valid
	*/
	function validate($directory)
	{
		$this->validator = new ilObjSCORMValidator($directory);
		$returnValue = $this->validator->validate();
		return $returnValue;
	}

	function getValidationSummary()
	{
		if(is_object($this->validator))
		{
			return $this->validator->getSummary();
		}
		return "";
	}

	function getTrackingItems()
	{
		return ilObjSCORMLearningModule::_getTrackingItems($this->getId());
	}


	/**
	* get all tracking items of scorm object
	* @access static
	*/
	function _getTrackingItems($a_obj_id)
	{
		include_once("./Modules/ScormAicc/classes/SCORM/class.ilSCORMTree.php");
		$tree = new ilSCORMTree($a_obj_id);
		$root_id = $tree->readRootId();

		$items = array();
		$childs = $tree->getSubTree($tree->getNodeData($root_id));

		foreach($childs as $child)
		{
			if($child["c_type"] == "sit")
			{
				include_once("./Modules/ScormAicc/classes/SCORM/class.ilSCORMItem.php");
				$sc_item =& new ilSCORMItem($child["obj_id"]);
				if ($sc_item->getIdentifierRef() != "")
				{
					$items[count($items)] =& $sc_item;
				}
			}
		}

		return $items;
	}

	/**
	* read manifest file
	* @access	public
	*/
	function readObject()
	{
		global $ilErr;
		
		$needs_convert = false;

		// convert imsmanifest.xml file in iso to utf8 if needed

		$manifest_file = $this->getDataDirectory()."/imsmanifest.xml";

		// check if manifestfile exists and space left on device...
		$check_for_manifest_file = is_file($manifest_file);

		// if no manifestfile
		if (!$check_for_manifest_file)
		{
			$this->ilias->raiseError($this->lng->txt("Manifestfile $manifest_file not found!"), $this->ilias->error_obj->MESSAGE);
			return;
		}

		if ($check_for_manifest_file)
		{
			$manifest_file_array = file($manifest_file);
			foreach($manifest_file_array as $mfa)
			{
				// if (seems_not_utf8($mfa))
				if (@iconv('UTF-8', 'UTF-8', $mfa) != $mfa) 
				{
					$needs_convert = true;
					break;
				}
			}

			// to copy the file we need some extraspace, counted in bytes *2 ... we need 2 copies....
			$estimated_manifest_filesize = filesize($manifest_file) * 2;
			
			// i deactivated this, because it seems to fail on some windows systems (see bug #1795)
			//$check_disc_free = disk_free_space($this->getDataDirectory()) - $estimated_manifest_filesize;
			$check_disc_free = 2;
		}

		// if $manifest_file needs to be converted to UTF8
		if ($needs_convert)
		{
			// if file exists and enough space left on device
			if ($check_for_manifest_file && ($check_disc_free > 1))
			{

				// create backup from original
				if (!copy($manifest_file, $manifest_file.".old"))
				{
					echo "Failed to copy $manifest_file...<br>\n";
				}

				// read backupfile, convert each line to utf8, write line to new file
				// php < 4.3 style
				$f_write_handler = fopen($manifest_file.".new", "w");
				$f_read_handler = fopen($manifest_file.".old", "r");
				while (!feof($f_read_handler))
				{
					$zeile = fgets($f_read_handler);
					//echo mb_detect_encoding($zeile);
					fputs($f_write_handler, utf8_encode($zeile));
				}
				fclose($f_read_handler);
				fclose($f_write_handler);

				// copy new utf8-file to imsmanifest.xml
				if (!copy($manifest_file.".new", $manifest_file))
				{
					echo "Failed to copy $manifest_file...<br>\n";
				}

				if (!@is_file($manifest_file))
				{
					$this->ilias->raiseError($this->lng->txt("cont_no_manifest"),
					$this->ilias->error_obj->WARNING);
				}
			}
			else
			{
				// gives out the specific error

				if (!($check_disc_free > 1))
					$this->ilias->raiseError($this->lng->txt("Not enough space left on device!"),$this->ilias->error_obj->MESSAGE);
					return;
			}

		}
		else
		{
			// check whether file starts with BOM (that confuses some sax parsers, see bug #1795)
			$hmani = fopen($manifest_file, "r");
			$start = fread($hmani, 3);
			if (strtolower(bin2hex($start)) == "efbbbf")
			{
				$f_write_handler = fopen($manifest_file.".new", "w");
				while (!feof($hmani))
				{
					$n = fread($hmani, 900);
					fputs($f_write_handler, $n);
				}
				fclose($f_write_handler);
				fclose($hmani);

				// copy new utf8-file to imsmanifest.xml
				if (!copy($manifest_file.".new", $manifest_file))
				{
					echo "Failed to copy $manifest_file...<br>\n";
				}
			}
			else
			{
				fclose($hmani);
			}
		}

		//validate the XML-Files in the SCORM-Package
		if ($_POST["validate"] == "y")
		{
			if (!$this->validate($this->getDataDirectory()))
			{
				$ilErr->raiseError("<b>Validation Error(s):</b><br>".$this->getValidationSummary(),$ilErr->MESSAGE);
			}
		}

		// start SCORM package parser
		include_once ("./Modules/ScormAicc/classes/SCORM/class.ilSCORMPackageParser.php");
		// todo determine imsmanifest.xml path here...
		$slmParser = new ilSCORMPackageParser($this, $manifest_file);
		$slmParser->startParsing();
		return $slmParser->getPackageTitle();
	}

	/**
	* set settings for learning progress determination per default at upload
	*/
	function setLearningProgressSettingsAtUpload()
	{
		global $ilSetting;
		//condition 1
		if ($ilSetting->get('scorm_lp_auto_activate',0)) return;
		//condition 2
		include_once("./Services/Tracking/classes/class.ilObjUserTracking.php");
		if (ilObjUserTracking::_enabledLearningProgress() == false) return; 
		
		//set Learning Progress to Automatic by Collection of SCORM Items
		include_once("./Services/Tracking/classes/class.ilLPObjSettings.php");
		$lm_set = new ilLPObjSettings($this->getId());
		$lm_set->setMode(ilLPObjSettings::LP_MODE_SCORM);
		$lm_set->insert();
		
		//select all SCOs as relevant for Learning Progress
		include_once("Services/Tracking/classes/collection/class.ilLPCollectionOfSCOs.php");
		$collection = new ilLPCollectionOfSCOs($this->getId(), ilLPObjSettings::LP_MODE_SCORM);
		$scos = array();
		foreach($collection->getPossibleItems() as $sco_id => $item)
		{
			$scos[] = $sco_id;
		}
		$collection->activateEntries($scos);
	}
	/**
	* get all tracked items of current user
	*/
	function getTrackedItems()
	{
		global $ilDB, $ilUser;
		
		$sco_set = $ilDB->queryF('
		SELECT DISTINCT sco_id FROM scorm_tracking WHERE obj_id = %s', 
		array('integer'),array($this->getId()));

		$items = array();
		while($sco_rec = $ilDB->fetchAssoc($sco_set))
		{
			include_once("./Modules/ScormAicc/classes/SCORM/class.ilSCORMItem.php");
			$sc_item =& new ilSCORMItem($sco_rec["sco_id"]);
			if ($sc_item->getIdentifierRef() != "")
			{
				$items[count($items)] =& $sc_item;
			}
		}

		return $items;
	}
	
	/**
	* Return the last access timestamp for a given user
	*
	* @param	int		$a_obj_id		object id
	* @param	int		$user_id		user id
	* @return timestamp
	*/
	public static function _lookupLastAccess($a_obj_id, $a_usr_id)
	{
		global $ilDB;

		$result = $ilDB->queryF('
		SELECT last_access FROM sahs_user 
		WHERE  obj_id = %s
		AND user_id = %s',
		array('integer','integer'), array($a_obj_id,$a_usr_id));
		
		if ($ilDB->numRows($result))
		{
			$row = $ilDB->fetchAssoc($result);
			return $row["last_access"];
		}
		return "";
	}

	function getTrackedUsers($a_search)
	{
		global $ilDB, $ilUser;
//TODO: UK last_access is not correct if no Commit or last_visited_sco
//		$query = 'SELECT user_id,MAX(c_timestamp) last_access, lastname, firstname FROM scorm_tracking st ' .
		$query = 'SELECT user_id, last_access, lastname, firstname FROM sahs_user st ' .
			'JOIN usr_data ud ON st.user_id = ud.usr_id ' .
			'WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer');
		if($a_search) {
//			$query .= ' AND (' . $ilDB->like('lastname', 'text', '%' . $a_search . '%') . ' OR ' . $ilDB->like('firstname', 'text', '%' . $a_search . '%') .')';
			$query .= ' AND ' . $ilDB->like('lastname', 'text', '%' . $a_search . '%');
		}
		$query .= ' GROUP BY user_id, lastname, firstname';
		$sco_set = $ilDB->query($query);

		$items = array();
		while($sco_rec = $ilDB->fetchAssoc($sco_set))
		{
			$items[] = $sco_rec;
		}
		return $items;
	}


	/**
	 * Get attempts for all users
	 * @global ilDB $ilDB
	 * @return array 
	 */
	public function getAttemptsForUsers()
	{
		global $ilDB;
		$query = 'SELECT user_id, package_attempts FROM sahs_user WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer') . ' ';
		$res = $ilDB->query($query);

		$attempts = array();
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$attempts[$row['user_id']] = (int) $row['package_attempts'];
		}
		return $attempts;
	}


	/**
	* get number of atttempts for a certain user and package
	*/
	function getAttemptsForUser($a_user_id){
		global $ilDB;
		$val_set = $ilDB->queryF('SELECT package_attempts FROM sahs_user WHERE obj_id = %s AND user_id = %s',
		array('integer','integer'),
		array($this->getId(),$a_user_id,0));

		$val_rec = $ilDB->fetchAssoc($val_set);
		
		if ($val_rec["package_attempts"] == null) {
			$val_rec["package_attempts"]="";
		}
		return $val_rec["package_attempts"];
	}


	/**
	 * Get module version for users.
	 * @global ilDB $ilDB
	 */
	public function getModuleVersionForUsers()
	{
		global $ilDB;
		$query = 'SELECT user_id, module_version FROM sahs_user WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer') . ' ';
		$res = $ilDB->query($query);

		$versions = array();
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$versions[$row['user_id']] = (int) $row['module_version'];
		}
		return $versions;
	}


	/**
	* get module version that tracking data for a user was recorded on
	*/
	function getModuleVersionForUser($a_user_id){
		global $ilDB;
		$val_set = $ilDB->queryF('SELECT module_version FROM sahs_user WHERE obj_id = %s AND user_id = %s',
		array('integer','integer'),
		array($this->getId(),$a_user_id,0));

		$val_rec = $ilDB->fetchAssoc($val_set);
		
		if ($val_rec["module_version"] == null) {
			$val_rec["module_version"]="";
		}
		return $val_rec["module_version"];
	}

	/**
	 * Get tracking data per user
	 * @global ilDB $ilDB
	 * @param int $a_sco_id
	 * @param int $a_user_id
	 * @return array
	 */
	function getTrackingDataPerUser($a_sco_id, $a_user_id)
	{
		global $ilDB;

		$data_set = $ilDB->queryF('
		SELECT * FROM scorm_tracking 
		WHERE user_id = %s
		AND sco_id = %s
		AND obj_id = %s
		ORDER BY lvalue',
		array('integer','integer','integer'),
		array($a_user_id,$a_sco_id,$this->getId()));
			
		$data = array();
		while($data_rec = $ilDB->fetchAssoc($data_set)) {
			$data[] = $data_rec;
		}

		return $data;
	}

	function getTrackingDataAgg($a_user_id)
	{
		global $ilDB;

		// get all users with any tracking data
		$sco_set = $ilDB->queryF('
		SELECT DISTINCT sco_id FROM scorm_tracking 
		WHERE obj_id = %s
		AND user_id = %s
		AND sco_id <> %s',
		array('integer','integer','integer'),
		array($this->getId(),$a_user_id,0));

		$data = array();
		while($sco_rec = $ilDB->fetchAssoc($sco_set))		
		{
			$data_set = $ilDB->queryF('
			SELECT * FROM scorm_tracking 
			WHERE  obj_id = %s
			AND sco_id = %s
			AND user_id = %s 
			AND lvalue <> %s
			AND (lvalue = %s
				OR lvalue = %s
				OR lvalue = %s)',
			array('integer','integer','integer','text','text','text','text'),
			array($this->getId(),
				$sco_rec["sco_id"],
				$a_user_id,
				"package_attempts",
				"cmi.core.lesson_status",
				"cmi.core.total_time",
				"cmi.core.score.raw")
			);
			
			$score = $time = $status = "";
			
			while($data_rec = $ilDB->fetchAssoc($data_set))
			{
				switch($data_rec["lvalue"])
				{
					case "cmi.core.lesson_status":
						$status = $data_rec["rvalue"];
						break;

					case "cmi.core.total_time":
						$time = $data_rec["rvalue"];
						break;

					case "cmi.core.score.raw":
						$score = $data_rec["rvalue"];
						break;
				}
			}
			//create sco_object
			include_once './Modules/ScormAicc/classes/SCORM/class.ilSCORMItem.php';
			$sc_item =& new ilSCORMItem($sco_rec["sco_id"]);
			$data[] = array("sco_id"=>$sco_rec["sco_id"], "title" => $sc_item->getTitle(),
			"score" => $score, "time" => $time, "status" => $status);
				
		}
		return (array) $data;
	}

	function getTrackingDataAggSco($a_sco_id)
	    {
	        global $ilDB;

	        // get all users with any tracking data
	        $user_set = $ilDB->queryF('
	        SELECT DISTINCT user_id FROM scorm_tracking 
	        WHERE obj_id = %s
	        AND sco_id = %s',
	        array('integer','integer'),
	        array($this->getId(),$a_sco_id));

	        $data = array();
	        while($user_rec = $ilDB->fetchAssoc($user_set))
	        {

	            $data_set = $ilDB->queryF('
	            SELECT * FROM scorm_tracking 
	            WHERE obj_id = %s
	            AND sco_id = %s
	            AND user_id = %s
	            AND (lvalue = %s
	            OR lvalue = %s
	            OR lvalue = %s)',
				array('integer','integer','integer','text','text','text'),
				array($this->getId(),
					$a_sco_id,
					$user_rec["user_id"],
					"cmi.core.lesson_status",
					"cmi.core.total_time",
					"cmi.core.score.raw")
				);
				
	      	  	$score = $time = $status = "";
	      	  	
	            while($data_rec = $ilDB->fetchAssoc($data_set))
	            {
	                switch($data_rec["lvalue"])
	                {
	                    case "cmi.core.lesson_status":
	                        $status = $data_rec["rvalue"];
	                        break;

	                    case "cmi.core.total_time":
	                        $time = $data_rec["rvalue"];
	                        break;

	                    case "cmi.core.score.raw":
	                        $score = $data_rec["rvalue"];
	                        break;
	                }
	            }

	            $data[] = array("user_id" => $user_rec["user_id"],
	                "score" => $score, "time" => $time, "status" => $status);
	        }

	        return $data;
	    }	
	

	/**
	 * Export 
	 * @global ilDB $ilDB
	 * @global ilObjUser $ilUser
	 * @param bool $a_exportall
	 * @param array $a_user 
	 */
	public function exportSelectedRaw($a_exportall, $a_user = array())
	{
		global $ilDB, $ilUser, $ilSetting;

		$inst_id = $ilSetting->get('inst_id',0);

		include_once './Services/Utilities/classes/class.ilCSVWriter.php';
		$csv = new ilCSVWriter();
		$csv->setSeparator(';');
		$csv->addColumn('Scoid');
		$csv->addColumn('Key');
		$csv->addColumn('Value');
		$csv->addColumn('Email');
		$csv->addColumn('Timestamp');
		$csv->addColumn('Userid');

		// Collect users
		$user_array = array();
		if($a_exportall)
		{
			$res = $ilDB->queryF(
					'SELECT user_id FROM scorm_tracking WHERE obj_id = %s GROUP BY user_id',
					array('integer'),
					array($this->getId())
			);
			while($row = $ilDB->fetchAssoc($res))
			{
				$user_array[] = $row['user_id'];
			}
		}
		else
		{
			$user_array = $a_user;
		}

		// Read user data
		$query = "SELECT usr_id,email FROM usr_data ".
			"WHERE ".$ilDB->in('usr_id', $user_array, FALSE, 'integer');
		$res = $ilDB->query($query);
		$emails = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$emails[$row->usr_id] = $row->email;
		}

		foreach($user_array as $user_id)
		{
			// Sco related information
			$query = 'SELECT rvalue, lvalue, identifierref, c_timestamp FROM scorm_tracking st '.
				'JOIN sc_item si ON st.sco_id = si.obj_id '.
				'WHERE user_id = '.$ilDB->quote($user_id,'integer'). ' '.
				'AND st.obj_id = '.$ilDB->quote($this->getId(),'integer');
			$res = $ilDB->query($query);
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$csv->addRow();
				$csv->addColumn($row->identifierref);
				$csv->addColumn($row->lvalue);
				$csv->addColumn($row->rvalue);
				$csv->addColumn(isset($emails[$user_id]) ? (string) $emails[$user_id] : '');
				$csv->addColumn($row->c_timestamp);
				$csv->addColumn('il_usr_'.$inst_id.'_'.$user_id);
			}
			// Sco unrelated information
			$query = 'SELECT package_attempts,module_version,last_visited,last_access FROM sahs_user '.
				'WHERE user_id = '.$ilDB->quote($user_id,'integer').' '.
				'AND obj_id = '.$ilDB->quote($this->getId(),'integer');
			$res = $ilDB->query($query);
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				if ($row->package_attempts != null) {
					$csv->addRow();
					$csv->addColumn(0);
					$csv->addColumn("package_attempts");
					$csv->addColumn($row->package_attempts);
					$csv->addColumn(isset($emails[$user_id]) ? (string) $emails[$user_id] : '');
					$csv->addColumn($row->last_access);
					$csv->addColumn('il_usr_'.$inst_id.'_'.$user_id);
				}
				if ($row->last_visited != null) {
					$csv->addRow();
					$csv->addColumn(0);
					$csv->addColumn("last_visited");
					$csv->addColumn($row->last_visited);
					$csv->addColumn(isset($emails[$user_id]) ? (string) $emails[$user_id] : '');
					$csv->addColumn($row->last_access);
					$csv->addColumn('il_usr_'.$inst_id.'_'.$user_id);
				}
				if ($row->module_version != null) {
					$csv->addRow();
					$csv->addColumn(0);
					$csv->addColumn("module_version");
					$csv->addColumn($row->module_version);
					$csv->addColumn(isset($emails[$user_id]) ? (string) $emails[$user_id] : '');
					$csv->addColumn($row->last_access);
					$csv->addColumn('il_usr_'.$inst_id.'_'.$user_id);
				}
			}
			//before 4.4
			// $query = 'SELECT rvalue, lvalue, c_timestamp FROM scorm_tracking '.
				// 'WHERE sco_id = 0 AND user_id = '.$ilDB->quote($user_id,'integer').' '.
				// 'AND obj_id = '.$ilDB->quote($this->getId(),'integer');
			// $res = $ilDB->query($query);
			// while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			// {
				// $csv->addRow();
				// $csv->addColumn(0);
				// $csv->addColumn($row->lvalue);
				// $csv->addColumn($row->rvalue);
				// $csv->addColumn(isset($emails[$user_id]) ? (string) $emails[$user_id] : '');
				// $csv->addColumn($row->c_timestamp);
				// $csv->addColumn('il_usr_'.$inst_id.'_'.$user_id);
			// }
		}

		ilUtil::deliverData(
				$csv->getCSVString(),
				'scorm_tracking_raw_' . $this->getRefId() . '_' . time() . '.csv'
		);
		return;
	}
	
	

	/**
	 * Export selected user tracking data
	 * @global ilDB $ilDB
	 * @global ilObjUser $ilUser
	 * @param bool $a_all
	 * @param array $a_users
	 */
	public function exportSelected($a_all, $a_users = array())
	{
		global $ilDB, $ilUser, $ilSetting;

		$inst_id = $ilSetting->get('inst_id',0);

		// Get all scos
		$scos = array();

		//get all SCO's of this object
		$query = 'SELECT scorm_object.obj_id, scorm_object.title, '
			. 'scorm_object.c_type, scorm_object.slm_id, scorm_object.obj_id scoid  '
			. 'FROM scorm_object, sc_item, sc_resource '
			. 'WHERE (scorm_object.slm_id = %s '
			. 'AND scorm_object.obj_id = sc_item.obj_id '
			. 'AND sc_item.identifierref = sc_resource.import_id '
			. 'AND sc_resource.scormtype = %s) '
			. 'GROUP BY scorm_object.obj_id, scorm_object.title, scorm_object.c_type,  '
			. 'scorm_object.slm_id, scorm_object.obj_id ';
		$res = $ilDB->queryF(
				$query,
				array('integer', 'text'),
				array($this->getId(), 'sco')
		);
		while($row = $ilDB->fetchAssoc($res))
		{
			$scos[] = $row['scoid'];
		}


		$users = array();
		if($a_all)
		{
			$query = 'SELECT user_id FROM scorm_tracking ' .
				'WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer') . ' ' .
				'GROUP BY user_id';
			$res = $ilDB->query($query);
			while($row = $ilDB->fetchAssoc($res))
			{
				$users[] = $row['user_id'];
			}
		}
		else
		{
			$users = $a_users;
		}

		// get all completed
		include_once './Modules/ScormAicc/classes/SCORM/class.ilObjSCORMTracking.php';
		$completed = ilObjSCORMTracking::_getCompleted($scos, $this->getId());
		$last = ilObjSCORMTracking::lookupLastAccessTimes($this->getId());

		include_once './Services/Utilities/classes/class.ilCSVWriter.php';
		$csv = new ilCSVWriter();
		$csv->setSeparator(';');
		foreach(array('Department', 'Login', 'Lastname', 'Firstname', 'Email', 'Date', 'Status') as $col)
		{
			$csv->addColumn($col);
		}

		// Read user data
		$query = 'SELECT usr_id,login,firstname,lastname,department,email ' .
			'FROM usr_data ' .
			'WHERE ' . $ilDB->in('usr_id', $users, false, 'integer');
		$res = $ilDB->query($query);

		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$csv->addRow();
			$csv->addColumn((string) $row->department);
			$csv->addColumn((string) $row->login);
			$csv->addColumn((string) $row->lastname);
			$csv->addColumn((string) $row->firstname);
			$csv->addColumn((string) $row->email);
			if(isset($last[$row->usr_id]))
			{
				$dt = new ilDateTime($last[$row->usr_id], IL_CAL_DATETIME);
				$csv->addColumn((string) $dt->get(IL_CAL_FKT_DATE, 'd.m.Y'));
			}
			else
			{
				$csv->addColumn('');
			}
			$csv->addColumn(in_array($row->usr_id, $completed) ? 1 : 0);
		}


		ilUtil::deliverData(
				$csv->getCSVString(),
				'scorm_tracking_' . $this->getRefId() . '_' . time() . '.csv'
		);
	}
	
	
	function importTrackingData($a_file)
	{
		global $ilDB, $ilUser;
		
		$error = 0;
		//echo file_get_contents($a_file);
		$method = null;
		
		//lets import
		$fhandle = fopen($a_file, "r");
		
		//the top line is the field names
		$fields = fgetcsv($fhandle, pow(2, 16), ';');
		//lets check the import method
		fclose($fhandle);
	   
		switch($fields[0])
		{
			case "Scoid": 
				$error = $this->importRaw($a_file);
				break;
			case "Department":
				$error = $this->importSuccess($a_file);
				break;
			default:
				return -1;
				break;
		}
		return $error;
	}
	
	function importSuccess($a_file) {
		
		global $ilDB, $ilUser;
		
		$scos = array();
		//get all SCO's of this object

	    $val_set = $ilDB->queryF('
		    SELECT 	scorm_object.obj_id, 
		    		scorm_object.title, 
		    		scorm_object.c_type,
		    		scorm_object.slm_id, 
		    		scorm_object.obj_id scoid 
		    FROM scorm_object,sc_item,sc_resource
		    WHERE(scorm_object.slm_id = %s
		    AND scorm_object.obj_id=sc_item.obj_id 
		    AND sc_item.identifierref = sc_resource.import_id 
		    AND sc_resource.scormtype = %s)
		    GROUP BY scorm_object.obj_id,
		    		scorm_object.title,
		    		scorm_object.c_type,
		    		scorm_object.slm_id,
		    		scorm_object.obj_id ',
		    array('integer','text'),
		    array($this->getId(),'sco')
	    );
	    
 		if (count($val_set)<1)
		{
			return -1;
		}			
		while($rows_sco = $ilDB->fetchAssoc($val_set))
		{
			array_push($scos,$rows_sco['scoid']);
		}
		
		$fhandle = fopen($a_file, "r");

		$obj_id = $this->getID();

		$fields = fgetcsv($fhandle, pow(2, 16), ';');
		$users = array();
		while(($csv_rows = fgetcsv($fhandle, pow(2, 16), ";")) !== FALSE)
		{
			$data = array_combine($fields, $csv_rows);
			  //check the format
			  $statuscheck = 0;
			  if (count($csv_rows) == 6) {$statuscheck = 1;}
			
			  if ($this->get_user_id($data["Login"])>0) {
					
				$user_id = $this->get_user_id($data["Login"]);
				$users[] = $user_id;
				$import = $data["Status"];
				if ($import == "") {$import = 1;}
					//iterate over all SCO's
					if ($import == 1) {
						foreach ($scos as $sco) 
						{
							$sco_id = $sco;

							$date_ex = explode('.', $data['Date']);
							$date = implode('-', array($date_ex[2], $date_ex[1], $date_ex[0]));
							
							$statement = $ilDB->queryF('
								SELECT * FROM scorm_tracking 
								WHERE user_id = %s
								AND sco_id = %s 
								AND lvalue = %s
								AND obj_id = %s',
								array('integer','integer','text','integer'),
								array($user_id, $sco_id, 'cmi.core.lesson_status',$obj_id)
							);
							if($ilDB->numRows($statement) > 0)
							{
								$ilDB->update('scorm_tracking',
									array(
										'rvalue'		=> array('clob', 'completed'),
										'c_timestamp'	=> array('timestamp', $date)
									),
									array(
										'user_id'		=> array('integer', $user_id),
										'sco_id'		=> array('integer', $sco_id),
										'lvalue'		=> array('text', 'cmi.core.lesson_status'),
										'obj_id'		=> array('integer', $obj_id)
									)
								);
							}
							else
							{
								$ilDB->insert('scorm_tracking', array(
									'obj_id'		=> array('integer', $obj_id),
									'user_id'		=> array('integer', $user_id),
									'sco_id'		=> array('integer', $sco_id),
									'lvalue'		=> array('text', 'cmi.core.lesson_status'),
									'rvalue'		=> array('clob', 'completed'),
									'c_timestamp'	=> array('timestamp', $date)
								));
							}							
						
							$statement = $ilDB->queryF('
								SELECT * FROM scorm_tracking 
								WHERE user_id = %s
								AND sco_id = %s 
								AND lvalue = %s
								AND obj_id = %s',
								array('integer','integer','text','integer'),
								array($user_id, $sco_id, 'cmi.core.entry',$obj_id)
							);
							if($ilDB->numRows($statement) > 0)
							{
								$ilDB->update('scorm_tracking',
									array(
										'rvalue'		=> array('clob', 'completed'),
										'c_timestamp'	=> array('timestamp', $date)
									),
									array(
										'user_id'		=> array('integer', $user_id),
										'sco_id'		=> array('integer', $sco_id),
										'lvalue'		=> array('text', 'cmi.core.entry'),
										'obj_id'		=> array('integer', $obj_id)
									)
								);
							}
							else
							{
								$ilDB->insert('scorm_tracking', array(
									'obj_id'		=> array('integer', $obj_id),
									'user_id'		=> array('integer', $user_id),
									'sco_id'		=> array('integer', $sco_id),
									'lvalue'		=> array('text', 'cmi.core.entry'),
									'rvalue'		=> array('clob', 'completed'),
									'c_timestamp'	=> array('timestamp', $date)
								));
							}							
						}
					}
			  	} else {
					//echo "Warning! User $csv_rows[0] does not exist in ILIAS. Data for this user was skipped.\n";
				}
		}
		
		include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
		ilLPStatusWrapper::_refreshStatus($this->getId());
//		<4.2.6: foreach ($users as $user_id) {ilLPStatusWrapper::_updateStatus($obj_id, $user_id);}
		return 0;
	}

	/**
	 * Parse il_usr_123_6 id
	 * @param <type> $il_id
	 * @return <type>
	 */
	private function parseUserId($il_id)
	{
		global $ilSetting;

		$parts = explode('_', $il_id);

		if(!count((array) $parts))
		{
			return 0;
		}
		if(!isset($parts[2]) or !isset($parts[3]))
		{
			return 0;
		}
		if($parts[2] != $ilSetting->get('inst_id',$parts[2]))
		{
			return 0;
		}
		return $parts[3];
	}

	/**
	 * Import raw data
	 * @global ilDB $ilDB
	 * @global ilObjUser $ilUser
	 * @param string $a_file
	 * @return void 
	 */
	private function importRaw($a_file)
	{
		global $ilDB, $ilUser;
		//no need to use sahs_user because never data was imported

		$fhandle = fopen($a_file, "r");

		$fields = fgetcsv($fhandle, pow(2, 16), ';');
		$users = array();
		while(($csv_rows = fgetcsv($fhandle, pow(2, 16), ";")) !== FALSE)
		{
			$data = array_combine($fields, $csv_rows);
			$user_id = $this->parseUserId($data['Userid']);

			if(!$user_id)
			{
				continue;
			}

			$il_sco_id = $this->lookupSCOId($data['Scoid']);

			//do the actual import
			if($il_sco_id >= 0)
			{
				$statement = $ilDB->queryF('
					SELECT * FROM scorm_tracking 
					WHERE user_id = %s
					AND sco_id = %s 
					AND lvalue = %s
					AND obj_id = %s',
						array('integer', 'integer', 'text', 'integer'),
						array($user_id, $il_sco_id, $data['Key'], $this->getID())
				);
				if($ilDB->numRows($statement) > 0)
				{
					$ilDB->update('scorm_tracking',
						array(
							'rvalue' => array('clob', $data['Value']),
							'c_timestamp' => array('timestamp', $data['Timestamp'])
						),
						array(
							'user_id' => array('integer', $user_id),
							'sco_id' => array('integer', $il_sco_id),
							'lvalue' => array('text', $data['Key']),
							'obj_id' => array('integer', $this->getId())
						)
					);
				}
				else
				{
					$ilDB->insert('scorm_tracking', array(
						'obj_id' => array('integer', $this->getId()),
						'user_id' => array('integer', $user_id),
						'sco_id' => array('integer', $il_sco_id),
						'lvalue' => array('text', $data['Key']),
						'rvalue' => array('clob', $data['Value']),
						'c_timestamp' => array('timestamp', $data['Timestamp'])
					));
				}
			}
		}
		fclose($fhandle);

		include_once './Services/Tracking/classes/class.ilLPStatusWrapper.php';
		ilLPStatusWrapper::_refreshStatus($this->getId());

		return 0;
	}
	
	//helper function
	function get_user_id($a_login) {
		global $ilDB, $ilUser;
		
		$val_set = $ilDB->queryF('SELECT * FROM usr_data WHERE(login=%s)',
		array('text'),array($a_login));
		$val_rec = $ilDB->fetchAssoc($val_set);
		
		if (count($val_rec)>0) {
			return $val_rec['usr_id'];
		} else {
			return null;
		}
	}
	
	
	/**
	* resolves manifest SCOID to internal ILIAS SCO ID
	*/
	private function lookupSCOId($a_referrer){
		global $ilDB, $ilUser;
		
		//non specific SCO entries
		if ($a_referrer=="0") {
			return 0;
		}

		$val_set = $ilDB->queryF('
		SELECT obj_id FROM sc_item,scorm_tree 
		WHERE (obj_id = child 
		AND identifierref = %s 
		AND slm_id = %s)',
		array('text','integer'), array($a_referrer,$this->getID()));
		$val_rec = $ilDB->fetchAssoc($val_set);
		
		return $val_rec["obj_id"];
	}
	
	/**
	* assumes that only one account exists for a mailadress
	*/
	function getUserIdEmail($a_mail)
	{
		global $ilDB, $ilUser;
		
		$val_set = $ilDB->queryF('SELECT usr_id FROM usr_data WHERE(email=%s)',
		array('text'),array($a_mail));
		$val_rec = $ilDB->fetchAssoc($val_set);
				
		
		return $val_rec["usr_id"];
	}
	
	
	/**
	* send export file to browser
	*/
	function sendExportFile($a_header,$a_content)
	{
	   	$timestamp = time();
		$refid = $this->getRefId();
		$filename = "scorm_tracking_".$refid."_".$timestamp.".csv";
		//Header
		header("Expires: 0");
		header("Cache-control: private");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Description: File Transfer");
		header("Content-Type: application/octet-stream");
		header("Content-disposition: attachment; filename=$filename");
		echo $a_header.$a_content;
		exit;	
	}
	
	/**
	* Get an array of id's for all Sco's in the module
	* @param int $a_id Object id
	* @return array Sco id's
	*/
	public static function _getAllScoIds($a_id)
	{
		global $ilDB;
		
		$scos = array();

		$val_set = $ilDB->queryF('
		SELECT scorm_object.obj_id,
				scorm_object.title,
				scorm_object.c_type,
				scorm_object.slm_id,
				scorm_object.obj_id scoid 
		FROM scorm_object,sc_item,sc_resource 
		WHERE(scorm_object.slm_id = %s
		AND scorm_object.obj_id = sc_item.obj_id 
		AND sc_item.identifierref = sc_resource.import_id 
		AND sc_resource.scormtype = %s)
		GROUP BY scorm_object.obj_id,
				scorm_object.title,
				scorm_object.c_type,
				scorm_object.slm_id,
				scorm_object.obj_id ',
		array('integer', 'text'),
		array($a_id,'sco'));

		while ($val_rec = $ilDB->fetchAssoc($val_set)) 
		{
			array_push($scos,$val_rec['scoid']);
		}
		return $scos;
	}
	
	/**
	* Get the status of a SCORM module for a given user
	* @param int $a_id Object id
	* @param int $a_user User id
	* @param array $a_allScoIds Array of Sco id's in this module
	* @param boolean $a_numerical Text (false) or boolean result (true)
	* @return mixed Status result
	*/
	public static function _getStatusForUser($a_id, $a_user,$a_allScoIds,$a_numerical=false)
	{
		global $ilDB, $lng;
		
		$scos = $a_allScoIds;
		//check if all SCO's are completed
		$scos_c = implode(',',$scos);

		$val_set = $ilDB->queryF('
		SELECT * FROM scorm_tracking 
		WHERE (user_id = %s
		AND obj_id = %s
		AND '.$ilDB->in('sco_id', $scos, false, 'integer').'
		AND ((lvalue = %s AND '.$ilDB->like('rvalue', 'clob', 'completed').') 
			OR (lvalue = %s AND '.$ilDB->like('rvalue', 'clob', 'passed').')))',
		array('integer','integer','text','text'),
		array($a_user,$a_id,'cmi.core.lesson_status', 'cmi.core.lesson_status'));	
		while ($val_rec = $ilDB->fetchAssoc($val_set))
		{
			$key = array_search($val_rec['sco_id'], $scos); 
			unset ($scos[$key]);
		}
		//check for completion
		if (count($scos) == 0) {
			$completion = ($a_numerical===true)  ? true: $lng->txt("cont_complete");
		}	
		if (count($scos) > 0) {
			$completion = ($a_numerical===true)  ? false: $lng->txt("cont_incomplete");
		}
		return $completion;
	}

	/**
	* Get the completion of a SCORM module for a given user
	* @param int $a_id Object id
	* @param int $a_user User id
	* @return boolean Completion status
	*/
	public static function _getCourseCompletionForUser($a_id, $a_user) 
	{
		return ilObjSCORMLearningModule::_getStatusForUser($a_id, $a_user, ilObjSCORMLearningModule::_getAllScoIds($a_id), true);
	}

	function getAllScoIds(){
		global $ilDB;
		
		$scos = array();
		//get all SCO's of this object
		$val_set = $ilDB->queryF('
		SELECT scorm_object.obj_id,
				scorm_object.title,
				scorm_object.c_type,
				scorm_object.slm_id,
				scorm_object.obj_id scoid 
		FROM scorm_object, sc_item,sc_resource 
		WHERE(scorm_object.slm_id = %s 
			AND scorm_object.obj_id = sc_item.obj_id 
			AND sc_item.identifierref = sc_resource.import_id 
			AND sc_resource.scormtype = %s )
		GROUP BY scorm_object.obj_id,
		scorm_object.title,
		scorm_object.c_type,
		scorm_object.slm_id,
		scorm_object.obj_id',
		array('integer','text'), 
		array($this->getId(),'sco'));

		while ($val_rec = $ilDB->fetchAssoc($val_set))
		{
			array_push($scos,$val_rec['scoid']);
		}
		return $scos;
	}
	
	function getStatusForUser($a_user,$a_allScoIds,$a_numerical=false){
		global $ilDB;
		$scos = $a_allScoIds;
		//loook up status
		//check if all SCO's are completed
		$scos_c = implode(',',$scos); 

		$val_set = $ilDB->queryF('
		SELECT sco_id FROM scorm_tracking 
		WHERE (user_id = %s
			AND obj_id = %s
			AND '.$ilDB->in('sco_id', $scos, false, 'integer').'
		 AND ((lvalue = %s AND '.$ilDB->like('rvalue', 'clob', 'completed').') OR (lvalue =  %s AND '.$ilDB->like('rvalue', 'clob', 'passed').') ) )',
		array('integer','integer','text','text',), 
		array($a_user,$this->getID(),'cmi.core.lesson_status','cmi.core.lesson_status'));	
		while ($val_rec = $ilDB->fetchAssoc($val_set))	
		{
			$key = array_search($val_rec['sco_id'], $scos); 
			unset ($scos[$key]);
		}		
		//check for completion
		if (count($scos) == 0) {
			$completion = ($a_numerical===true)  ? true: $this->lng->txt("cont_complete");
		}	
		if (count($scos) > 0) {
			$completion = ($a_numerical===true)  ? false: $this->lng->txt("cont_incomplete");
		}
		return $completion;
	}
	
	function getCourseCompletionForUser($a_user) {
		return $this->getStatusForUser($a_user,$this->getAllScoIds,true);
	}
	
	//to be called from IlObjUser
	public static function _removeTrackingDataForUser($user_id) {
		global $ilDB;
		//gobjective
		$ilDB->manipulateF(
			'DELETE FROM scorm_tracking WHERE user_id = %s',
			array('integer'),
			array($user_id)
		);
		$ilDB->manipulateF(
			'DELETE FROM sahs_user WHERE user_id = %s',
			array('integer'),
			array($user_id)
		);
	}
	
	function _getScoresForUser($a_item_id, $a_user_id)
	{
		global $ilDB;

		$retAr = array("raw" => null, "max" => null, "scaled" => null);
		$val_set = $ilDB->queryF("
			SELECT lvalue, rvalue FROM scorm_tracking 
			WHERE sco_id = %s 
			AND user_id =  %s
			AND (lvalue = 'cmi.core.score.raw' OR lvalue = 'cmi.core.score.max')",
			array('integer', 'integer'),
			array($a_item_id, $a_user_id)
		);
		while ($val_rec = $ilDB->fetchAssoc($val_set))
		{
			if ($val_rec['lvalue'] == "cmi.core.score.raw") $retAr["raw"] = $val_rec["rvalue"];
			if ($val_rec['lvalue'] == "cmi.core.score.max") $retAr["max"] = $val_rec["rvalue"];
		}
		if ($retAr["raw"] != null && $retAr["max"] != null) $retAr["scaled"] = ($retAr["raw"] / $retAr["max"]);

		return $retAr;
	}


	public function getLastVisited($user_id)
	{
		global $ilDB;
		$val_set = $ilDB->queryF('SELECT last_visited FROM sahs_user WHERE obj_id = %s AND user_id = %s',
			array('integer','integer'), 
			array($this->getID(),$user_id)
		);
		while ($val_rec = $ilDB->fetchAssoc($val_set))
		{
			if ($val_rec["last_visited"] != null) return "".$val_rec["last_visited"];
		}
		return '0';
	}

	function deleteTrackingDataOfUsers($a_users)
	{
		global $ilDB;
		include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");

		foreach($a_users as $user)
		{
			$ilDB->manipulateF('
				DELETE FROM scorm_tracking
				WHERE user_id = %s
				AND obj_id = %s',
				array('integer', 'integer'),
				array($user, $this->getID()));

			$ilDB->manipulateF('
				DELETE FROM sahs_user
				WHERE user_id = %s
				AND obj_id = %s',
				array('integer', 'integer'),
				array($user, $this->getID()));

			ilLPStatusWrapper::_updateStatus($this->getId(), $user);
		}
	}

}
?>
