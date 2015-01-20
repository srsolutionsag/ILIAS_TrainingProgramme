<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Search/classes/class.ilAbstractSearch.php';

/**
* Class ilLikeUserMultiFieldSearch
*
* Performs Mysql Like search in table usr_defined_data
*
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id: class.ilLikeUserMultiFieldSearch.php 53526 2014-09-18 12:50:40Z smeyer $
* 
* @package ilias-search
*
*/
class ilLikeUserMultiFieldSearch extends ilAbstractSearch
{

	/**
	* Constructor
	* @access public
	*/
	public function __construct($qp_obj)
	{
		parent::__construct($qp_obj);
	}
	
	/**
	 * Perform search
	 * @return type
	 */
	public function performSearch()
	{
		$where = $this->__createWhereCondition();
		$locate = $this->__createLocateString();

		$query = "SELECT usr_id  ".
			$locate.
			"FROM usr_data_multi ".
			$where;
		
		$GLOBALS['ilLog']->write(__METHOD__.': '.$query);
		
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->search_result->addEntry($row->usr_id,'usr',$this->__prepareFound($row));
		}
		return $this->search_result;
	}
	
	
	/**
	 * 
	 * @param
	 * @return
	 */
	public function setFields($a_fields)
	{
		foreach($a_fields as $field)
		{
			$fields[] = $field;
		}
		parent::setFields($fields ? $fields : array());
	}
	

	function __createWhereCondition()
	{
		global $ilDB;
		
		$fields = $this->getFields();
		$field = $fields[0];

		$and = "  WHERE field_id = ".$ilDB->quote($field, "text")." AND ( ";
		$counter = 0;
		foreach($this->query_parser->getQuotedWords() as $word)
		{
			if($counter++)
			{
				$and .= " OR ";
			}

			if(strpos($word,'^') === 0)
			{
				$and .= $ilDB->like("value", "text", substr($word,1)."%");
			}
			else
			{
				$and .= $ilDB->like("value", "text", "%".$word."%");
			}
		}
		return $and.") ";
	}
}
?>
