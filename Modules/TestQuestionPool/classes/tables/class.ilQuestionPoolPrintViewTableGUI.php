<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
*
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id: class.ilQuestionPoolPrintViewTableGUI.php 54090 2014-10-07 14:42:32Z bheyser $
*
* @ingroup ModulesQuestionPool
*/

class ilQuestionPoolPrintViewTableGUI extends ilTable2GUI
{	
	protected $outputmode;
	
	protected $totalPoints;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $outputmode = '')
	{
		$this->setId("qpl_print");
		parent::__construct($a_parent_obj, $a_parent_cmd);

		global $lng, $ilCtrl;

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->outputmode = $outputmode;
	
		$this->setFormName('printviewform');
		$this->setStyle('table', 'fullwidth');

		$this->addCommandButton('print', $this->lng->txt('print'), "javascript:window.print();return false;");

		$this->setRowTemplate("tpl.il_as_qpl_printview_row.html", "Modules/TestQuestionPool");

		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setDefaultOrderField("title");
		$this->setDefaultOrderDirection("asc");
		$this->setLimit(999);
		
		$this->enable('sort');
		$this->enable('header');
//		$this->disable('numinfo');
		$this->disable('select_all');
	}
	
	public function initColumns()
	{
		$this->addColumn($this->lng->txt("title"),'title', '');
		
		foreach ($this->getSelectedColumns() as $c)
		{
			if (strcmp($c, 'description') == 0) $this->addColumn($this->lng->txt("description"),'description', '');
			if (strcmp($c, 'author') == 0) $this->addColumn($this->lng->txt("author"),'author', '');
			if (strcmp($c, 'ttype') == 0) $this->addColumn($this->lng->txt("question_type"),'ttype', '');
			if (strcmp($c, 'points') == 0) $this->addColumn($this->getPointsColumnHeader(),'points', '');
			if (strcmp($c, 'created') == 0) $this->addColumn($this->lng->txt("create_date"),'created', '');
			if (strcmp($c, 'updated') == 0) $this->addColumn($this->lng->txt("last_update"),'updated', '');
		}
	}
	
	private function getPointsColumnHeader()
	{
		return $this->lng->txt("points") . ' ('.$this->getTotalPoints().')';
	}

	function getSelectableColumns()
	{
		global $lng;
		$cols["description"] = array(
			"txt" => $lng->txt("description"),
			"default" => true
		);
		$cols["author"] = array(
			"txt" => $lng->txt("author"),
			"default" => true
		);
		$cols["ttype"] = array(
			"txt" => $lng->txt("question_type"),
			"default" => true
		);
		$cols["points"] = array(
			"txt" => $lng->txt("points"),
			"default" => true
		);
		$cols["created"] = array(
			"txt" => $lng->txt("create_date"),
			"default" => true
		);
		$cols["updated"] = array(
			"txt" => $lng->txt("last_update"),
			"default" => true
		);
		return $cols;
	}

	/**
	 * fill row 
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function fillRow($data)
	{
		ilDatePresentation::setUseRelativeDates(false);
		$this->tpl->setVariable("TITLE", ilUtil::prepareFormOutput($data['title']));
		foreach ($this->getSelectedColumns() as $c)
		{
			if (strcmp($c, 'description') == 0)
			{
				$this->tpl->setCurrentBlock('description');
				$this->tpl->setVariable("DESCRIPTION", ilUtil::prepareFormOutput($data['description']));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'author') == 0)
			{
				$this->tpl->setCurrentBlock('author');
				$this->tpl->setVariable("AUTHOR", ilUtil::prepareFormOutput($data['author']));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'ttype') == 0)
			{
				$this->tpl->setCurrentBlock('ttype');
				$this->tpl->setVariable("TYPE", ilUtil::prepareFormOutput($data['ttype']));
				$this->tpl->parseCurrentBlock();
			}
			if (strcmp($c, 'points') == 0)
			{
				$this->tpl->setCurrentBlock('points');
				$this->tpl->setVariable("POINTS", ilUtil::prepareFormOutput($data['points']));
				$this->tpl->parseCurrentBlock();
			}
			if(strcmp($c, 'created') == 0)
			{
				$this->tpl->setCurrentBlock('created');
				$this->tpl->setVariable('CREATED', ilDatePresentation::formatDate(new ilDateTime($data['created'], IL_CAL_UNIX)));
				$this->tpl->parseCurrentBlock();
			}
			if(strcmp($c, 'updated') == 0)
			{
				$this->tpl->setCurrentBlock('updated');
				$this->tpl->setVariable('UPDATED', ilDatePresentation::formatDate(new ilDateTime($data['updated'], IL_CAL_UNIX)));
				$this->tpl->parseCurrentBlock();
			}
		}
		if ((strcmp($this->outputmode, "detailed") == 0) || (strcmp($this->outputmode, "detailed_printview") == 0))
		{
			$this->tpl->setCurrentBlock("overview_row_detail");
			include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
			$question_gui = assQuestion::_instanciateQuestionGUI($data["question_id"]);
			if (strcmp($this->outputmode, "detailed") == 0)
			{
				$solutionoutput = $question_gui->getSolutionOutput($active_id = "", $pass = NULL, $graphicalOutput = FALSE, $result_output = FALSE, $show_question_only = FALSE, $show_feedback = FALSE, $show_correct_solution = true, $show_manual_scoring = false);
				if (strlen($solutionoutput) == 0) $solutionoutput = $question_gui->getPreview();
				$this->tpl->setVariable("DETAILS", $solutionoutput);
			}
			else
			{
				$this->tpl->setVariable("DETAILS", $question_gui->getPreview());
			}
			$this->tpl->parseCurrentBlock();
		}
		ilDatePresentation::setUseRelativeDates(true);
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function numericOrdering($column)
	{
		if(in_array($column, array('created', 'updated')))
		{
			return true;
		}

		return false;
	}

	public function getTotalPoints()
	{
		return $this->totalPoints;
	}

	public function setTotalPoints($totalPoints)
	{
		$this->totalPoints = $totalPoints;
	}
}