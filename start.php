<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * startpage for ilias
 * this file decides if a frameset is used or not.
 * Frames set definition is done in 'tpl.start.html'
 * 
 * @author Peter Gabriel <pgabriel@databay.de>
 * @package ilias-core
 * @version $Id: start.php 33447 2012-03-01 14:00:01Z jluetzen $
*/

require_once "./Services/Utilities/classes/class.ilUtil.php";
ilUtil::redirect("index.php");

?>