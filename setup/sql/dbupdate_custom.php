<#1>
<?php

require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgramme.php");
require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeAssignment.php");
require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeProgress.php");

ilStudyProgramme::installDB();
ilStudyProgrammeAssignment::installDB();
ilStudyProgrammeProgress::installDB();

?>

<#2>
<?php

require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeProgress.php");
require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeAssignment.php");


// Active Record does not support tuples as primary keys, so we have to
// set those on our own.
$ilDB->addUniqueConstraint( ilStudyProgrammeProgress::returnDbTableName()
						  , array("assignment_id", "prg_id", "usr_id")
						  );

?>

<#3>
<?php

// ActiveRecord seems to not interpret con_is_null correctly, so we have to set
// it manually.

require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeProgress.php");

$ilDB->modifyTableColumn( ilStudyProgrammeProgress::returnDbTableName()
						, "completion_by"
						, array( "notnull" => false
							   , "default" => null
							   )
						);

?>

<#4>
<?php

// ActiveRecord seems to not interpret con_is_null correctly, so we have to set
// it manually.

require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeProgress.php");

ilStudyProgrammeProgress::updateDB();
$ilDB->modifyTableColumn( ilStudyProgrammeProgress::returnDbTableName()
						, "last_change_by"
						, array( "notnull" => false
							   , "default" => null
							   )
						);
?>

<#5>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#6>
<?php
	// added listener for Services/Tracking
	$ilCtrlStructureReader->getStructure();
?>

<#7>
<?php

require_once("./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php");
$obj_type_id = ilDBUpdateNewObjectType::addNewType("prg", "StudyProgramme");
$existing_ops = array("visible", "read", "write", "copy", "delete", "edit_permission");
foreach ($existing_ops as $op) {
	$op_id = ilDBUpdateNewObjectType::getCustomRBACOperationId($op);
	ilDBUpdateNewObjectType::addRBACOperation($obj_type_id, $op_id);		
}
?>

<#8>
<?php

require_once("./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php");
$obj_type_id = ilDBUpdateNewObjectType::getObjectTypeId("prg");
$op_id = ilDBUpdateNewObjectType::addCustomRBACOperation("manage_members" , "Manage Members", 'object', 300);
ilDBUpdateNewObjectType::addRBACOperation($obj_type_id, $op_id);

?>
<#9>
<?php

require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeAdvancedMetadataRecord.php");
require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeType.php");
require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeTypeTranslation.php");

ilStudyProgrammeAdvancedMetadataRecord::installDB();
ilStudyProgrammeType::installDB();
ilStudyProgrammeTypeTranslation::installDB();

?>

<#10>
<?php
// reload
$ilCtrlStructureReader->getStructure();
?>

<#11>
<?php
require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeType.php");

// ActiveRecord seems to not interpret con_is_null correctly, so we have to set
// it manually.

ilStudyProgrammeType::updateDB();
$ilDB->modifyTableColumn( ilStudyProgrammeType::returnDbTableName()
	, "last_update"
	, array( "notnull" => false
	, "default" => null
	)
);
?>

<#12>
<?php
// reload
$ilCtrlStructureReader->getStructure();
?>