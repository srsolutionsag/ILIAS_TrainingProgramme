<#1>
<?php

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgramme.php");
require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeAssignment.php");
require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeProgress.php");

ilTrainingProgramme::installDB();
ilTrainingProgrammeAssignment::installDB();
ilTrainingProgrammeProgress::installDB();

?>
