<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/classes/class.ilXmlExporter.php");

/**
 * Used for container export with tests
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @version $Id: class.ilTestExporter.php 44245 2013-08-17 11:15:45Z mbecker $
 * @ingroup ModulesTest
 */
class ilTestExporter extends ilXmlExporter
{
	private $ds;

	/**
	 * Initialisation
	 */
	function init()
	{
	}


	/**
	 * Get xml representation
	 *
	 * @param	string		entity
	 * @param	string		schema version
	 * @param	string		id
	 * @return	string		xml string
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
	{
		include_once './Modules/Test/classes/class.ilObjTest.php';
		$tst = new ilObjTest($a_id,false);

		include_once("./Modules/Test/classes/class.ilTestExport.php");
		$test_exp = new ilTestExport($tst, 'xml');
		$zip = $test_exp->buildExportFile();
		
		$GLOBALS['ilLog']->write(__METHOD__.': Created zip file '.$zip);
	}

	/**
	 * Returns schema versions that the component can export to.
	 * ILIAS chooses the first one, that has min/max constraints which
	 * fit to the target release. Please put the newest on top.
	 *
	 * @return
	 */
	function getValidSchemaVersions($a_entity)
	{
		return array (
			"4.1.0" => array(
				"namespace" => "http://www.ilias.de/Modules/Test/htlm/4_1",
				"xsd_file" => "ilias_tst_4_1.xsd",
				"uses_dataset" => false,
				"min" => "4.1.0",
				"max" => "")
		);
	}

}

?>