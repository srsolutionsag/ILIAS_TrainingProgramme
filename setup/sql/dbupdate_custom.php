<#1>
<?php

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgramme.php");
require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeAssignment.php");
require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeProgress.php");

ilTrainingProgramme::installDB();
ilTrainingProgrammeAssignment::installDB();
ilTrainingProgrammeProgress::installDB();

?>

<#2>
<?php

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeProgress.php");
require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeAssignment.php");


// Active Record does not support tuples as primary keys, so we have to
// set those on our own.
$ilDB->addUniqueConstraint( ilTrainingProgrammeProgress::returnDbTableName()
						  , array("assignment_id", "prg_id", "usr_id")
						  );

?>

<#3>
<?php

// ActiveRecord seems to not interpret con_is_null correctly, so we have to set
// it manually.

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeProgress.php");

$ilDB->modifyTableColumn( ilTrainingProgrammeProgress::returnDbTableName()
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

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeProgress.php");

ilTrainingProgrammeProgress::updateDB();
$ilDB->modifyTableColumn( ilTrainingProgrammeProgress::returnDbTableName()
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
$obj_type_id = ilDBUpdateNewObjectType::addNewType("prg");
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