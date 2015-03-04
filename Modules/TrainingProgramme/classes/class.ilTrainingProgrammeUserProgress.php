<?php

/* Copyright (c) 2015 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeProgress.php");

/**
 * Represents the progress of a user at one node of a training programme.
 *
 * A user could have multiple progress' on one node, since he could also have
 * multiple assignments to one node.
 *
 * @author : Richard Klees <richard.klees@concepts-and-training.de>
 */
class ilTrainingProgrammeUserProgress {
	protected $progress; // ilTrainingProgrammeProgress
	
	/**
	 * Throws when id does not refer to a training programme progress.
	 *
	 * Expects an array [assignment_id, program_node_id, user_id] or an
	 * ilTrainingProgress as first parameter.
	 *
	 * @throws ilException
	 * @param int[] | ilTrainingProgrammeAssignment $a_ids_or_model 
	 */
	public function __construct($a_ids_or_model) {
		if ($a_ids_or_model instanceof ilTrainingProgrammeProgress) {
			$this->progress = $a_ids_or_model;
		}
		else {
			if (count($a_ids_or_model) != 3) {
				throw new ilException("ilTrainingProgrammeUserProgress::__construct: "
									 ."expected array with 3 items.");
			}
			
			// TODO: ActiveRecord won't be caching the model objects, since
			// we are not using find. Maybe we should do this ourselves??
			// Or should we instead cache in getInstance?
			$this->progress = array_shift(
				ilTrainingProgrammeProgress::where(array
							( "assignment_id" => $a_ids_or_model[0]
							, "prg_id" => $a_ids_or_model[1]
							, "usr_id" => $a_ids_or_model[2]
							))->get());
		}
		if ($this->progress === null) {
			throw new ilException("ilTrainingProgrammeUserProgress::__construct: "
								 ."Unknown progress id '$a_id'.");
		}
	}
	
	/**
	 * Get an instance. Just wraps constructor.
	 *
	 * @throws ilException
	 * @param  int $a_assignment_id
	 * @param  int $a_program_id
	 * @param  int $a_user_id
	 * @return ilTrainingProgrammeUserProgress
	 */
	static public function getInstance($a_assignment_id, $a_program_id, $a_user_id) {
		return new ilTrainingProgrammeUserAssignment(array($a_assignment_id, $a_program_id, $a_user_id));
	}
	
	/**
	 * Get the instances that user has on program.
	 *
	 * @param  int $a_program_id
	 * @param  int $a_user_id
	 * @return ilTrainingProgrammeUserProgress[]
	 */
	static public function getInstancesForUser($a_program_id, $a_user_id) {
		$progresses = ilTrainingProgrammeProgress::where(array
							( "prg_id" => $a_program_id
							, "usr_id" => $a_user_id
							))->get();
		return array_values(array_map(function($dat) {
			return new ilTrainingProgrammeUserProgress($dat);
		}, $progresses));
	}
	
	/**
	 * Get the instance for the assignment on the program.
	 *
	 * Throws when the node does not belong to the assignment.
	 *
	 * @throws ilException
	 * @param  int $a_program_id
	 * @param  int $a_user_id
	 * @return ilTrainingProgrammeUserProgress
	 */
	static public function getInstanceForAssignment($a_program_id, $a_assignment_id) {
		$progresses = ilTrainingProgrammeProgress::where(array
							( "prg_id" => $a_program_id
							, "assignment_id" => $a_assignment_id
							))->get();
		if (count($progresses) == 0) {
			throw new ilException("ilTrainingProgrammeUserProgress::getInstanceForAssignment: "
								 ."Assignment '$a_assignment_id' does not belong to program "
								 ."'$a_program_id'");
		}
		return new ilTrainingProgrammeUserProgress(array_shift($progresses));
	}
	
	/**
	 * Get the instances for a program node.
	 *
	 * @param int $a_program_id
	 * @return ilTrainingProgrammeUserProgress[]
	 */
	static public function getInstancesForProgram($a_program_id) {
		$progresses = ilTrainingProgrammeProgress::where(array
							( "prg_id" => $a_program_id
							))->get();
		return array_values(array_map(function($dat) {
			return new ilTrainingProgrammeUserProgress($dat);
		}, $progresses));
	}
	
	/**
	 * Get the program node where this progress belongs to was made. 
	 *
	 * Throws when program this assignment is about has no ref id.
	 *
	 * @throws ilException
	 * @return ilObjTrainingProgramme
	 */
	public function getTrainingProgramme() {
		require_once("./Modules/TrainingProgramme/classes/class.ilObjTrainingProgramme.php");
		$refs = ilObject::_getAllReferences($this->progress->getNodeId());
		if (!count($refs)) {
			throw new ilException("ilTrainingProgrammeUserAssignment::getTrainingProgramme: "
								 ."could not find ref_id for program '"
								 .$this->progress->getNodeId()."'.");
		}
		return ilObjTrainingProgramme::getInstanceByRefId(array_shift($refs));
	}
	
	/**
	 * Get the assignment this progress belongs to.
	 *
	 * @return ilTrainingProgrammeUserAssignment
	 */
	public function getAssignment() {
		return ilTrainingProgrammeUserAssignment::getInstance($this->progress->getAssignmentId());
	}
	
	/**
	 * Get the id of the user who is assigned.
	 *
	 * @return int
	 */
	public function getUserId() {
		return $this->progress->getUserId();
	}
	
	/**
	 * Get the status of the progress.
	 *
	 * @return ilTrainingProgrammeProgress::$STATUS
	 */
	public function getStatus() {
		return $this->progress->getStatus();
	}
	
	/**
	 * Get the amount of points needed to complete the node. This is the amount
	 * of points yielded for the completion of the node above as well.
	 *
	 * @return int
	 */
	public function getAmountOfPoints() {
		return $this->progress->getAmountOfPoints();
	}
	
	/**
	 * Get the amount of points the user currently achieved.
	 *
	 * @return int
	 */
	public function getCurrentAmountOfPoints() {
		return $this->progress->getCurrentAmountOfPoints();
	}
	
	/**
	 * Get the timestamp when the last change was made on this progress.
	 *
	 * @return ilDateTime
	 */
	public function getLastChange() {
		return $this->progress->getLastChange();
	}
	
	/**
	 * Get the id of the user who did the last change on this progress.
	 *
	 * @return int
	 */
	public function getLastChangeBy() {
		return $this->progress->getLastChangeBy();
	}
	
	/**
	 * Get the id of the user or course that lead to completion of this node.
	 *
	 * @return int | null
	 */
	public function getCompletionBy() {
		return $this->progress->getCompletionBy();
	}

	/**
	 * Delete the assignment from database.
	 */
	public function delete() {
		$this->progress->delete();
	}
	
	
	/**
	 * Mark this progress as accredited.
	 *
	 * Throws when status is not IN_PROGRESS.
	 *
	 * @throws ilException
	 * @param int $a_user_id The user who performed the operation.
	 * @return $this
	 */
	public function markAccredited($a_user_id) {
		$this->progress->setStatus(ilTrainingProgrammeProgress::STATUS_ACCREDITED)
					   ->setCompletionBy($a_user_id)
					   ->setLastChangeBy($a_user_id)
					   ->update();
		return $this;
	}
	
	/**
	 * Set the node to be not relevant for the user.
	 *
	 * Throws when status is not IN_PROGRESS.
	 *
	 * @throws ilException
	 * @param  int $a_user_id The user who marks the node as not relevant.
	 * @return $this
	 */
	public function markNotRelevant($a_user_id) {
		$this->progress->setStatus(ilTrainingProgrammeProgress::STATUS_NOT_RELEVANT)
					   ->setCompletionBy($a_user_id)
					   ->setLastChangeBy($a_user_id)
					   ->update();
		return $this;
	}
	
	/**
	 * Set the amount of points the user is required to have to complete this node.
	 *
	 * Throws when status is completed.
	 *
	 * @throws ilException
	 * @param int $a_points    The amount of points the user needs for completion.
	 * @param int $a_user_id   The id of the user who did the modification.
	 * @return $this
	 */
	public function setRequiredAmountOfPoints($a_points, $a_user_id) {
		$this->progress->setAmountOfPoints($a_points)
					   ->setLastChangeBy($a_user_id)
					   ->update();
		return $this;
	}
	
	/**
	 * Get the maximum possible amount of points a user can achieve for
	 * the completion of this node.
	 *
	 * If the program node runs in LP-mode this will be equal getAmountOfPoints.
	 *
	 * TODO: Maybe caching this value would be a good idea.
	 *
	 * @return int
	 */
	public function getMaximumPossibleAmountOfPoints() {
		$prg = $this->getTrainingProgramme();
		if ($prg->getLPMode() == ilTrainingProgramme::MODE_LP_COMPLETED) {
			return $this->getAmountOfPoints();
		}
		$children = $prg->getChildren();
		$ass = $this->progress->getAssignmentId();
		$points = array_map(function($child) use ($ass) {
			return $child->getProgressForAssignment($ass)->getAmountOfPoints();
		}, $children);
		
		return array_reduce($points, function($a, $b) { return $a + $b; }, 0);
	}
	
	/**
	 * Check whether the user can achieve enough points on the subnodes to
	 * be able to complete this node.
	 *
	 * @return bool
	 */
	public function canBeCompleted() {
		return $this->getMaximumPossibleAmountOfPoints() >= $this->getAmountOfPoints();
	}
	
	/**
	 * Check whether there are individual modifications for the user on this program.
	 *
	 * @return bool
	 */
	public function hasIndividualModifications() {
		return $this->getLastChangeBy() !== null;
	}

	/**
	 * Update the progress from its program node. Will only update when the node
	 * does not have individual modifications and is not completed.
	 * Return false, when update could not be performed and true otherwise.
	 *
	 * @return bool
	 */
	public function updateFromProgramNode() {
		if ($this->hasIndividualModifications()) {
			return false;
		}
		if ($this->getStatus() == ilTrainingProgrammeProgress::STATUS_COMPLETED) {
			return false;
		}
		
		$prg = $this->getTrainingProgramme();
		$this->progress->setAmountOfPoints($prg->getPoints())
					   ->setStatus($prg->getStatus() == ilTrainingProgramme::STATUS_ACTIVE 
					   				? ilTrainingProgrammeProgress::STATUS_NOT_RELEVANT
					   				: ilTrainingProgrammeProgress::STATUS_IN_PROGRESS
					   			   )
					   ->update();
	}
}

?>