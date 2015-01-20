<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('Modules/Test/exceptions/class.ilTestException.php');

/**
 * Test Evaluation Exception
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.ilTestEvaluationException.php 44245 2013-08-17 11:15:45Z mbecker $
 * 
 * @ingroup ModulesTest
 */
class ilTestEvaluationException extends ilTestException
{
	/**
	 * ilTestException Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct($a_message,$a_code = 0)
	{
	 	parent::__construct($a_message,$a_code);
	}
}

