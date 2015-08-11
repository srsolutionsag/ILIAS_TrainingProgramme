<?php

/* Copyright (c) 2015 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

/**
 * Class ilStudyProgrammeExpandableProgressListGUI
 *
 * @author: Richard Klees <richard.klees@concepts-and-training.de>
 *
 */

require_once("Modules/StudyProgramme/classes/class.ilStudyProgrammeExpandableProgressListGUI.php");

class ilStudyProgrammeIndividualPlanProgressListGUI extends ilStudyProgrammeExpandableProgressListGUI {
	protected function showMyProgress() {
		// expand tree completely on start
		return true;
	}
	
	protected function newSubItem(ilStudyProgrammeUserProgress $a_progress) {
		return new ilStudyProgrammeIndividualPlanProgressListGUI($a_progress);
	}
	
	protected function buildProgressStatus(ilStudyProgrammeUserProgress $a_progress) {
		$points =  sprintf( $this->il_lng->txt("prg_progress_status")
						  , $a_progress->getCurrentAmountOfPoints()
						  , $a_progress->getAmountOfPoints()
						  );
		if (!$a_progress->canBeCompleted()) {
			return "<img src='".ilUtil::getImagePath("icon_alert.svg")."' alt='".$this->il_lng->txt("warning")."'>".$points;
		}
		else {
			return $points;
		}
	}
	
	protected function configureItemGUI(ilStudyProgrammeCourseListGUI $a_item_gui) {
		$a_item_gui->enableComments(false);
		$a_item_gui->enableTags(false);
		$a_item_gui->enableIcon(true);
		$a_item_gui->enableDelete(false);
		$a_item_gui->enableCut(false);
		$a_item_gui->enableCopy(false);
		$a_item_gui->enablePayment(false);
		$a_item_gui->enableLink(false);
		$a_item_gui->enableInfoScreen(false);
		$a_item_gui->enableSubscribe(false);
		$a_item_gui->enableCheckbox(false);
		$a_item_gui->enableDescription(true);
		$a_item_gui->enableProperties(false);
		$a_item_gui->enablePreconditions(false);
		$a_item_gui->enableNoticeProperties(false);
		$a_item_gui->enableCommands(false, true);
		$a_item_gui->enableProgressInfo(false);
		$a_item_gui->setIndent($this->getIndent() + 2);
	}
}