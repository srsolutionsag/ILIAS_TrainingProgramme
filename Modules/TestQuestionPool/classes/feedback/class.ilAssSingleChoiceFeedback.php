<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/TestQuestionPool/classes/feedback/class.ilAssConfigurableMultiOptionQuestionFeedback.php';

/**
 * feedback class for assSingleChoice questions
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.ilAssSingleChoiceFeedback.php 47237 2014-01-14 13:52:33Z bheyser $
 * 
 * @package		Modules/TestQuestionPool
 */
class ilAssSingleChoiceFeedback extends ilAssConfigurableMultiOptionQuestionFeedback
{
	/**
	 * table name for specific feedback
	 */
	const SPECIFIC_QUESTION_TABLE_NAME = 'qpl_qst_sc';

	/**
	 * returns the table name for specific question itself
	 *
	 * @return string $specificFeedbackTableName
	 */
	protected function getSpecificQuestionTableName()
	{
		return self::SPECIFIC_QUESTION_TABLE_NAME;
	}
}
