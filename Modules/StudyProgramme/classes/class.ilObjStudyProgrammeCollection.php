<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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
require_once("./Modules/StudyProgramme/classes/class.ilObjStudyProgramme.php");

/**
 * Handles collections of ilObjStudyProgramme-Objects
 * The class extends the ArrayObject class. This allows the class to work as arrays.
 *
 * @see http://php.net/manual/de/class.arrayobject.php
 * @author Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class ilObjStudyProgrammeCollection extends ArrayObject {

	/**
	 * Initialize the ProgrammeCollection
	 * @param array $data
	 */
	public function __construct(array $data = array()) {
		parent::__construct($data);
	}

	/**
	 * Sets the value at the specified index to newval
	 *
	 * @param mixed $index
	 * @param ilObjStudyProgramme $newval
	 * @throws ilException
	 */
	public function offsetSet($index, $newval) {
		if(!$this->typeCheck($newval)) {
			throw new ilException("You cannot add other types than ilObjStudyProgramme-Objects to ilObjStudyProgrammeCollections.");
		}

		parent::offsetSet($index, $newval);
	}

	/**
	 * Append ilObjStudyProgramme to the collection
	 *
	 * @param ilObjStudyProgramme $value
	 * @throws ilException
	 */
	public function append($value) {
		if(!$this->typeCheck($value)) {
			throw new ilException("You cannot add other types than ilObjStudyProgramme-Objects to ilObjStudyProgrammeCollections.");
		}

		parent::append($value);
	}

	/**
	 * Check the type of the given value against ilObjStudyProgramme
	 *
	 * @param $value
	 * @return bool
	 */
	protected function typeCheck($value) {
		if($value instanceof ilObjStudyProgramme) {
			return true;
		}
		return false;
	}
}