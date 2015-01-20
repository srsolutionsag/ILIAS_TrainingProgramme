<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Services/Excel/classes/class.ilExcelWriterWrapper.php';

/**
* Class ilExcelWriterAdapter
*
* @author Stefan Meyer <meyer@leifos.com> 
* @version $Id: class.ilExcelWriterAdapter.php 24199 2010-06-11 08:09:26Z jluetzen $
* 
* @extends ilObjectGUI
*
*/

class ilExcelWriterAdapter
{
	var $workbook = null;

	var $format_bold = null;
	var $format_header = null;

	function ilExcelWriterAdapter($a_filename,$a_send = true)
	{
		global $ilias, $lng;
		
		if($a_send)
		{
			$this->workbook =& new ilExcelWriterWrapper();
			$this->workbook->send($a_filename);
		}
		else
		{
			$this->workbook =& new ilExcelWriterWrapper($a_filename);
		}
		
		if(strlen($tmp = ini_get('upload_tmp_dir')))
		{
			$this->workbook->setTempDir($tmp);
		}

		// to make UTF8 input work
		$this->workbook->setVersion(8);
		
		$this->__initFormatBold();
		$this->__initFormatHeader();
		$this->__initFormatTitle();
	}

	function &getWorkbook()
	{
		return $this->workbook;
	}

	function &getFormatBold()
	{
		return $this->format_bold;
	}
	function &getFormatHeader()
	{
		return $this->format_header;
	}
	function &getFormatTitle()
	{
		return $this->format_title;
	}
	function &getFormatDate()
	{
		return $this->format_date;
	}
	function &getFormatDayTime()
	{
		return $this->format_day_time;
	}

	// PROTECTED
	function __initFormatBold()
	{
		$this->format_bold =& $this->workbook->addFormat();
		$this->format_bold->setBold();
	}
	function __initFormatHeader()
	{
		$this->format_header =& $this->workbook->addFormat();
		$this->format_header->setBold();
		$this->format_header->setTop(100);
		$this->format_header->setColor('black');
		$this->format_header->setPattern(1);
		$this->format_header->setFgColor('silver');
	}
	function __initFormatTitle()
	{
		$this->format_title =& $this->workbook->addFormat();
		$this->format_title->setBold();
		$this->format_title->setColor('black');
		$this->format_title->setPattern(1);
		$this->format_title->setSize(16);
		$this->format_title->setAlign('center');
	}
	function __initFormatDate()
	{
		$this->format_date =& $this->workbook->addFormat();
		$this->format_date->setNumFormat("YYYY-MM-DD hh:mm:ss");
	}

	function __initFormatDayTime()
	{
		$this->format_day_time =& $this->workbook->addFormat();
		$this->format_day_time->setNumFormat("DD:hh:mm:ss");
	}


}
?>
