<?php

/* Copyright (c) 2015 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once("./Modules/TrainingProgramme/classes/model/class.ilTrainingProgrammeAssignment.php");

/**
 * Represents one assignment of a user to a training programme.
 *
 * A user could have multiple assignments per programme.
 *
 * @author : Richard Klees <richard.klees@concepts-and-training.de>
 */
class ilTrainingProgrammeUserAssignment {
	protected $assignment; // ilTrainingProgrammeAssignment
	
	/**
	 * Throws when id does not refer to a training programme assignment.
	 *
	 * @throws ilException
	 * @param int | ilTrainingProgrammeAssignment $a_id_or_model
	 */
	public function __construct($a_id_or_model) {
		if ($a_id_or_model instanceof ilTrainingProgrammeAssignment) {
			$this->assignment = $a_id_or_model;
		}
		else {
			$this->assignment = ilTrainingProgrammeAssignment::find($a_id_or_model);
		}
		if ($this->assignment === null) {
			throw new ilException("ilTrainingProgrammeUserAssignment::__construct: "
								 ."Unknown assignmemt id '$a_id'.");
		}
	}
	
	/**
	 * Get an instance. Just wraps constructor.
	 *
	 * @throws ilException
	 * @param  int $a_id
	 * @return ilTrainingProgrammeUserAssignment
	 */
	static public function getInstance($a_id) {
		return new ilTrainingProgrammeUserAssignment($a_id);
	}
	
	/**
	 * Get the id of the assignment.
	 *
	 * @return int
	 */
	public function getId() {
		return $this->assignment->getId();
	}
	
	/**
	 * Get the program node where this assignment was made. 
	 *
	 * Throws when program this assignment is about has no ref id.
	 *
	 * @throws ilException
	 * @return ilObjTrainingProgramme
	 */
	public function getTrainingProgramme() {
		require_once("./Modules/TrainingProgramme/classes/class.ilObjTrainingProgramme.php");
		$refs = ilObject::_getAllReferences($this->assignment->getRootId());
		if (!count($refs)) {
			throw new ilException("ilTrainingProgrammeUserAssignment::getTrainingProgramme: "
								 ."could not find ref_id for program '"
								 .$this->assignment->getRootId()."'.");
		}
		return ilObjTrainingProgramme::getInstanceByRefId(array_shift($refs));
	}
	
	/**
	 * Get the id of the user who is assigned.
	 *
	 * @return int
	 */
	public function getUserId() {
		$this->assignment->getUserId();
	}
	
	/**
	 * Remove this assignment.
	 */
	public function remove() {
		return $this->getTrainingProgramme()->removeAssignment($this);
	}
	
	/**
	 * Delete the assignment from database.
	 */
	public function delete() {
		$this->assignment->delete();
	}
	
	/**
	 * Update all unmodified nodes in this assignment to the current state
	 * of the program.
	 *
	 * @return $this
	 */
	public function updateFromProgram() {
		
	}
}

?>