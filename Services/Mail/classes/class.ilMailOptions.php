<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

define("IL_MAIL_LOCAL", 0);
define("IL_MAIL_EMAIL", 1);
define("IL_MAIL_BOTH", 2);

/**
* Class UserMail
* this class handles user mails 
* 
*  
* @author	Stefan Meyer <meyer@leifos.com>
* @version $Id: class.ilMailOptions.php 34068 2012-04-06 07:08:36Z mjansen $
* 
*/
class ilMailOptions
{
	var $ilias;

	// SOME QUASI STATIC CONSTANTS (possible values of incoming type)
	var $LOCAL = 0;
	var $EMAIL = 1;
	var $BOTH = 2;

	/**
	* linebreak
	* @var integer
	* @access public
	*/
	var $linebreak;

	/**
	* signature
	* @var string signature
	* @access public
	*/
	var $signature;
	var $incoming_type;
	var $cronjob_notification;

	/**
	* Constructor
	* setup an mail object
	* @param int user_id
	* @access	public
	*/
	public function __construct($a_user_id)
	{
		global $ilias;

		define("DEFAULT_LINEBREAK",60);

		$this->ilias = $ilias;
		$this->table_mail_options = 'mail_options';

		$this->user_id = $a_user_id;
		$this->getOptions();
	}

	/**
	* create entry in table_mail_options for a new user
	* this method should only be called from createUser()
	* @access	public
	* @return	boolean
	*/
    function createMailOptionsEntry()
    {
    	global $ilDB;
    		
		/* Get setting for incoming mails */
		if (!($incomingMail = $this->ilias->getSetting("mail_incoming_mail")))
		{
			/* No setting found -> set it to "local and forwarding" [2] */
			$incomingMail = IL_MAIL_BOTH;
		}

		$statement = $ilDB->manipulateF('
			INSERT INTO '.$this->table_mail_options.'
			(	user_id, 
				linebreak, 
				signature, 
				incoming_type, 
				cronjob_notification
			)
			VALUES(%s, %s, %s, %s, %s)', 
			array('integer', 'integer', 'text', 'integer', 'integer'),
			array($this->user_id, DEFAULT_LINEBREAK, NULL, $incomingMail, '0'));
		
		return true;
    }

	/**
	* get options of user and set variables $signature and $linebreak
	* this method shouldn't bew called from outside
	* use getSignature() and getLinebreak()
	* @access	private
	* @return	boolean
	*/
	function getOptions()
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT * FROM '.$this->table_mail_options.'
			WHERE user_id = %s',
			array('integer'), array($this->user_id));
		
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		
		$this->cronjob_notification = stripslashes($row->cronjob_notification);
		$this->signature = stripslashes($row->signature);
		$this->linebreak = stripslashes($row->linebreak);
		$this->incoming_type = $row->incoming_type;
		
		if(!strlen(ilObjUser::_lookupEmail($this->user_id)))
		{
			$this->incoming_type = $this->LOCAL;
		}

		return true;
	}

	/**
	* update user options
	* @param string Signature
	* @param int linebreak
	* @param int incoming_type
	* @param int cronjob_notification
	* @return	boolean
	*/
	function updateOptions($a_signature, $a_linebreak, $a_incoming_type, $a_cronjob_notification)
	{
		global $ilDB, $ilias;

		$data = array();
		$data_types = array();
				
		$query = 'UPDATE '.$this->table_mail_options.' 
				SET signature = %s,
				linebreak = %s, ';
	
		array_push($data_types, 'text', 'integer');
		array_push($data, $a_signature, $a_linebreak);
		
		if ($ilias->getSetting('mail_notification'))
		{		
			$query .= 'cronjob_notification = %s, ';
			array_push($data_types, 'integer');
			array_push($data, $a_cronjob_notification);			
		}

		$query .='incoming_type = %s WHERE user_id =  %s';			
		array_push($data, $a_incoming_type, $this->user_id);
		array_push($data_types, 'integer', 'integer');
		
		$statement = $ilDB->manipulateF($query, $data_types, $data);
		
		$this->cronjob_notification = $a_cronjob_notification;
		$this->signature = $a_signature;
		$this->linebreak = $a_linebreak;
		$this->incoming_type = $a_incoming_type;

		return true;
	}
	/**
	* get linebreak of user
	* @access	public
	* @return	array	mails
	*/
	function getLinebreak()
	{
		return $this->linebreak;
	}

	/**
	* get signature of user
	* @access	public
	* @return	array	mails
	*/
	function getSignature()
	{
		return $this->signature;
	}

	function getIncomingType()
	{
		return $this->incoming_type;
	}
	
	function setCronjobNotification()
	{
		return $this->cronjob_notification;
	}
	function getCronjobNotification()
	{
		return $this->cronjob_notification;
	}
	
	
} // END class.ilFormatMail
?>
