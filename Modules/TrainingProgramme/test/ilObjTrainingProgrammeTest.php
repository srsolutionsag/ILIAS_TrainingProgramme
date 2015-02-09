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

/**
 * TestCase for the ilObjTrainingProgramme
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class ilObjTrainingProgrammeTest extends PHPUnit_Framework_TestCase {
    protected $backupGlobals = FALSE;

    protected static $temp_id;
    protected static $temp_ref_id;

    protected function setUp() {
        PHPUnit_Framework_Error_Deprecated::$enabled = FALSE;

        require_once("./Modules/TrainingProgramme/classes/class.ilObjTrainingProgramme.php");

        include_once("./Services/PHPUnit/classes/class.ilUnitUtil.php");
        ilUnitUtil::performInitialisation();
    }

    /**
     * Test creation of ilObjTrainingProgramme
     */
    public function testCreation() {
        $root_object = new ilObjTrainingProgramme();
        $root_object->create();

        self::$temp_id = $root_object->getId();
        self::$temp_ref_id = $root_object->createReference();

        $this->assertNotEmpty(self::$temp_id);
        $this->assertGreaterThan(0, self::$temp_id);

        $this->assertNotEmpty(self::$temp_ref_id);
        $this->assertGreaterThan(0, self::$temp_ref_id);
    }

    /**
     * Test loading of ilObjTrainingProgramme with obj_id and ref_id
     *
     * @depends testCreation
     */
    public function testLoad() {
        $load_obj_id = new ilObjTrainingProgramme(self::$temp_id, false);
        $load_ref_id = new ilObjTrainingProgramme(self::$temp_ref_id);

        $this->assertNotNull($load_obj_id);
        $this->assertGreaterThan(0, $load_obj_id->getId());

        $this->assertNotNull($load_ref_id);
        $this->assertGreaterThan(0, $load_obj_id->getId());
    }

    /**
     * Test loading over getInstance
     *
     * @depends testCreation
     */
    public function testGetInstance() {
        $obj = ilObjTrainingProgramme::getInstance(self::$temp_ref_id);

        $this->assertNotNull($obj);
        $this->assertEquals(self::$temp_ref_id, $obj->getRefId());
    }

    /**
     * Test settings on ilObjTrainingProgramme
     *
     * @depends testCreation
     */
    public function testSettings() {
        $obj = new ilObjTrainingProgramme(self::$temp_ref_id);

        $obj->setPoints(10);
        $obj->setStatus(ilTrainingProgramme::STATUS_ACTIVE);
        $obj->update();

        $obj = new ilObjTrainingProgramme(self::$temp_ref_id);

        $this->assertEquals(10, $obj->getPoints());
        $this->assertEquals(ilTrainingProgramme::STATUS_ACTIVE, $obj->getStatus());

        $midnight = strtotime("today midnight");
        $this->assertGreaterThan($midnight, $obj->getLastChange());
    }

    /**
     * Test deletion of a ilObjTrainingProgramme
     *
     * @depends testCreation
     */
    public function testDelete() {
        $deleted_object = new ilObjTrainingProgramme(self::$temp_ref_id);

        $this->assertTrue($deleted_object->delete());
    }

    /**
     * Test creating a small tree
     *
     * @depends testCreation
     */
    public function testTreeCreation() {
        $this->testCreation();

        $obj = new ilObjTrainingProgramme(self::$temp_ref_id);

        $first_node = new ilObjTrainingProgramme();
        $first_node->create();

        $second_node = new ilObjTrainingProgramme();
        $second_node->create();

        $obj->addNode($first_node);
        $obj->addNode($second_node);

        $this->assertEquals(2, $obj->getAmountOfChildren());
    }

    /**
     * Test function to get children or information about them
     *
     * @depends testTreeCreation
     */
    public function testTreeGetChildren() {
        $root = ilObjTrainingProgramme::getInstance(self::$temp_ref_id);

        $children = ilObjTrainingProgramme::getAllChildren(self::$temp_ref_id);
        $this->assertEquals(2, count($children), "ilObjTrainingProgramme::getAllChildren(".self::$temp_ref_id.")");

        $children = $root->getChildren();
        $this->assertEquals(2, count($children), "getChildren()");

        // Test
        $this->assertTrue($root->hasChildren(), "hasChildren()");
        $this->assertEquals(2, $root->getAmountOfChildren(), "getAmountOfChildren()");
    }

    /**
     * Test getParent on ilObjTrainingProgramme
     *
     * @depends testTreeCreation
     */
    public function testTreeGetParent() {
        $root = ilObjTrainingProgramme::getInstance(self::$temp_ref_id);
        $children = $root->getChildren();

        $child = $children[0];
        $this->assertNotNull($child->getParent());
        $this->assertNull($root->getParent());
    }

    /**
     * Test getDepth on ilObjTrainingProgramme
     *
     * @depends testTreeCreation
     */
    public function testTreeDepth() {
        $root = ilObjTrainingProgramme::getInstance(self::$temp_ref_id);
        $children = $root->getChildren();

        $child = $children[0];

        $this->assertEquals(1, $child->getDepth());
    }

    /**
     * Test getRoot on ilObjTrainingProgramme
     *
     * @depends testTreeCreation
     */
    public function testTreeGetRoot() {
        $root = ilObjTrainingProgramme::getInstance(self::$temp_ref_id);
        $children = $root->getChildren();
        $child = $children[0];

        $this->assertEquals($root->getId(), $child->getRoot()->getId());
    }

}