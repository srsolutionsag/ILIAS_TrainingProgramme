<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once "./Services/Container/classes/class.ilContainerGUI.php";

/**
 * Class ilObjiLincCourseGUI
 *
 * @author Sascha Hofmann <saschahofmann@gmx.de> 
 *
 * @version $Id: class.ilObjiLincCourseGUI.php 56827 2015-01-07 11:04:15Z akill $
 * 
 * @extends ilObjectGUI
 * 
 * @ilCtrl_Calls ilObjiLincCourseGUI: ilObjiLincClassroomGUI, ilPermissionGUI, ilInfoScreenGUI, ilRepositorySearchGUI
 * @ilCtrl_Calls ilObjiLincCourseGUI: ilPublicUserProfileGUI, ilColumnGUI
 * @ilCtrl_Calls ilObjiLincCourseGUI: ilCommonActionDispatcherGUI
 */
class ilObjiLincCourseGUI extends ilContainerGUI
{
	private $form_gui = null;
	
	/**
	* Constructor
	* @access public
	*/
	public function ilObjiLincCourseGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output = false)
	{
		$this->type = "icrs";
		$this->ilContainerGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);
		
		$this->ctrl->saveParameter($this,'ref_id');
		
		$this->lng->loadLanguageModule('ilinc');
	}
	
	/**
	* create new object form
	*
	* @access	public
	*/
	public function createObject()
	{
		global $rbacsystem;

		$new_type = $_POST['new_type'] ? $_POST['new_type'] : $_GET['new_type'];

		if(!$rbacsystem->checkAccess('create', $_GET['ref_id'], $new_type))
		{
			$this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
		}		
		
		$this->initSettingsForm('create');
		return $this->tpl->setVariable('ADM_CONTENT', $this->form_gui->getHtml());
	}
	
	public function updateObject()
	{
		global $ilAccess;
		
		if(!$ilAccess->checkAccess('write', '', (int)$_GET['ref_id']))
		{
			$this->ilErr->raiseError($this->lng->txt('permission_denied'), $this->ilErr->MESSAGE);
		}
		
		$this->initSettingsForm('edit');
		if($this->form_gui->checkInput())
		{
			$_POST['Fobject']['title'] = $this->form_gui->getInput('title');
			$_POST['Fobject']['desc'] = $this->form_gui->getInput('desc');	
			$_POST['Fobject']['activated'] = $this->form_gui->getInput('activated');
			$_POST['Fobject']['akclassvalue1'] = $this->form_gui->getInput('akclassvalue1');
			$_POST['Fobject']['akclassvalue2'] = $this->form_gui->getInput('akclassvalue2');
			
			$this->object->setTitle(ilUtil::prepareDBString($_POST['Fobject']['title']));
			$this->object->setDescription(ilUtil::prepareDBString($_POST['Fobject']['desc']));
			$this->object->activated = ilUtil::tf2yn($_POST['Fobject']['activated']);
			
			// update akclassvalues only if iLinc is active
			if($this->ilias->getSetting('ilinc_akclassvalues_active'))
			{
				if($this->object->getAKClassValue1() != $_POST['Fobject']['akclassvalue1'])
				{
					$this->object->setAKClassValue1(ilUtil::prepareDBString($_POST['Fobject']['akclassvalue1']));
				}
	
				if($this->object->getAKClassValue2() != $_POST['Fobject']['akclassvalue2'])
				{
					$this->object->setAKClassValue2(ilUtil::prepareDBString($_POST['Fobject']['akclassvalue2']));
				}			
			}
			
			// save changes to ilinc server and ilias database
			$success = $this->object->update();			
			if($success == false)
			{
				$this->ilErr->raiseError($this->object->getErrorMsg(), $this->ilErr->MESSAGE);
			}
			
			// update all akclassvalues of classes if akclassvalues has changed
			if(array_key_exists('akclassvalue1', $_POST['Fobject']) or 
			   array_key_exists('akclassvalue2',$_POST['Fobject']))
			{
				if(!$this->object->updateClassrooms())
				{
					ilUtil::sendInfo($this->lng->txt($this->object->getErrorMsg()));
					$this->form_gui->setValuesByPost();
					return $this->tpl->setVariable('ADM_CONTENT', $this->form_gui->getHtml());
				}
			}
						
			ilUtil::sendInfo($this->lng->txt('msg_obj_modified'));
			$this->form_gui->setValuesByPost();
			return $this->tpl->setVariable('ADM_CONTENT', $this->form_gui->getHtml());
		}
		else
		{
			$this->form_gui->setValuesByPost();
			return $this->tpl->setVariable('ADM_CONTENT', $this->form_gui->getHtml());
		}
	}
	
	/**
	* save object
	* @access	public
	*/
	public function saveObject()
	{
		$this->initSettingsForm('create');
		if($this->form_gui->checkInput())
		{				
			$_POST['Fobject']['title'] = $this->form_gui->getInput('title');
			$_POST['Fobject']['desc'] = $this->form_gui->getInput('desc');
			$_POST['Fobject']['activated'] = $this->form_gui->getInput('activated');
			$_POST['Fobject']['akclassvalue1'] = $this->form_gui->getInput('akclassvalue1');
			$_POST['Fobject']['akclassvalue2'] = $this->form_gui->getInput('akclassvalue2');
			
			// when creating new ilinc course we first create it on ilinc server
			include_once 'Modules/ILinc/classes/class.ilnetucateXMLAPI.php';
			$ilinc = new ilnetucateXMLAPI();
			$ilinc->addCourse($_POST['Fobject']);
			
			$this->iLincAddCourseResponse = $ilinc->sendRequest();			
			if($this->iLincAddCourseResponse->isError())
			{
				$this->ilErr->raiseError($this->iLincAddCourseResponse->getErrorMsg(), $this->ilErr->MESSAGE);
			}			
			
			// if everything ok
			parent::saveObject();
		}
		else
		{
			$this->form_gui->setValuesByPost();
			return $this->tpl->setVariable('ADM_CONTENT', $this->form_gui->getHtml());
		}
	}
	
	/**
	 * @param ilObjiLincCourse $a_new_object 
	 */
	protected function afterSave(ilObject $a_new_object)
	{
		// save ilinc_id in ILIAS and save data
		$a_new_object->storeiLincId($this->iLincAddCourseResponse->getFirstID());
		$a_new_object->saveActivationStatus(ilUtil::tf2yn((bool)$this->form_gui->getInput('activated')));
		$a_new_object->saveAKClassValues(
			$this->form_gui->getInput('akclassvalue1'), 
			$this->form_gui->getInput('akclassvalue2')
		);

		// ...finally assign icrsadmin role to creator of icrs object
		$success = $a_new_object->addMember($this->ilias->account, $a_new_object->getDefaultAdminRole(), true);

		if(!$success)
		{
			ilUtil::sendFailure($a_new_object->getErrorMsg(), true);
			$this->ctrl->returnToParent($this);
		}

//		$icrsObj->setRegistrationFlag($_POST["enable_registration"]); //0=no registration, 1=registration enabled 2=passwordregistration
//		$icrsObj->setPassword($_POST["password"]);
//		$icrsObj->setExpirationDateTime($_POST["expirationdate"]." ".$_POST["expirationtime"].":00");

		$this->ilias->account->addDesktopItem($a_new_object->getRefId(), 'icrs');	

		// always send a message
		ilUtil::sendInfo($this->lng->txt('icrs_added'), true);			
		$this->redirectToRefId((int)$_GET['ref_id']);
	}
	
	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	public function getTabs(&$tabs_gui)
	{
		global $rbacsystem,$ilAccess;

		$this->ctrl->setParameter($this,'ref_id',$this->ref_id);

		if($rbacsystem->checkAccess('read',$this->ref_id))
		{
			$tabs_gui->addTarget('ilinc_classrooms',
				$this->ctrl->getLinkTarget($this, ''),
				array('', 'view', 'editClassroom', 'updateClassroom', 'removeClassroom')
				);
		}
					
		if($this->ilias->getSetting('ilinc_active'))
		{
			if($ilAccess->checkAccess('write', '', $this->ref_id))
			{
				$tabs_gui->addTarget('edit_properties',
					$this->ctrl->getLinkTarget($this, 'edit'), array('edit', 'update', 'save'), get_class($this));
			}
	
		/*	if ($rbacsystem->checkAccess('read',$this->ref_id))
			{
				$tabs_gui->addTarget("ilinc_involved_users",
					$this->ctrl->getLinkTarget($this, "members"), array("members","mailMembers","membersGallery","showProfile"), get_class($this));
			}*/
			
			// member list
			if($ilAccess->checkAccess('write','',$this->ref_id))
			{
				$tabs_gui->addTarget("ilinc_involved_users",
									 $this->ctrl->getLinkTarget($this, "members"), 
									 array("members","mailMembers","membersGallery","showProfile",'changeMember', 'RemoveMember'),
									 get_class($this));
			}			
			elseif ($ilAccess->checkAccess('read','',$this->ref_id))
			{
				$tabs_gui->addTarget("ilinc_involved_users",
									 $this->ctrl->getLinkTarget($this, "membersGallery"), 
									 array("members","mailMembers","membersGallery","showProfile"),
									 get_class($this));
			}

			if ($rbacsystem->checkAccess('write',$this->ref_id) and $this->object->isDocent($this->ilias->account))
			{
				// testing: display link to ilinc server directly
				$tabs_gui->addTarget("ilinc_manage_course_documents",
					$url = $this->object->userLogin($this->ilias->account), "agenda","","_blank");
	//			$tabs_gui->addTarget("ilinc_manage_course_documents",
	//				$this->ctrl->getLinkTarget($this, "agenda"), "agenda", get_class($this),"_blank");
	
			}
		}
		
		/*$applications = $this->object->getNewRegistrations();

		if (is_array($applications) and $this->object->isAdmin($this->ilias->account->getId()))
		{
			$tabs_gui->addTarget("ilinc_new_registrations",
				$this->ctrl->getLinkTarget($this, "ShownewRegistrations"), "ShownewRegistrations", get_class($this));
		}*/

		if ($rbacsystem->checkAccess('edit_permission',$this->ref_id))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
		
		// show clipboard in repository
		if ($_GET["baseClass"] == "ilRepositoryGUI" and !empty($_SESSION['il_rep_clipboard']))
		{
			$tabs_gui->addTarget("clipboard",
				 $this->ctrl->getLinkTarget($this, "clipboard"), "clipboard", get_class($this));
		}

		if ($this->ctrl->getTargetScript() == "adm_object.php")
		{
			if ($this->tree->getSavedNodeData($this->ref_id))
			{
				$tabs_gui->addTarget("trash",
					$this->ctrl->getLinkTarget($this, "trash"), "trash", get_class($this));
			}
		}
	}
	
	public function __unsetSessionVariables()
	{
		unset($_SESSION["grp_delete_member_ids"]);
		unset($_SESSION["grp_delete_subscriber_ids"]);
		unset($_SESSION["grp_search_str"]);
		unset($_SESSION["grp_search_for"]);
		unset($_SESSION["grp_role"]);
		unset($_SESSION["grp_group"]);
		unset($_SESSION["grp_archives"]);
	}
	
	public function __search($a_search_string,$a_search_for)
	{
		include_once("./Services/Search/classes/class.ilSearch.php");

		$this->lng->loadLanguageModule("content");
		$search =& new ilSearch($_SESSION["AccountId"]);
		$search->setPerformUpdate(false);
		$search->setSearchString(ilUtil::stripSlashes($a_search_string));
		$search->setCombination("and");
		$search->setSearchFor(array(0 => $a_search_for));
		$search->setSearchType('new');

		if($search->validate($message))
		{
			$search->performSearch();
		}
		else
		{
			ilUtil::sendInfo($message,true);
			$this->ctrl->redirect($this,"searchUserForm");
		}

		return $search->getResultByType($a_search_for);
	}

	public function __showSearchUserTable($a_result_set,$a_user_ids = NULL, $a_cmd = "search")
	{
        $return_to  = "searchUserForm";
	
    	if ($a_cmd == "listUsersRole" or $a_cmd == "listUsersGroup")
    	{
            $return_to = "search";
        }

		$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",$return_to);
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("back"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","addUser");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("add"));
		$tpl->parseCurrentBlock();
		
		if (!empty($a_user_ids))
		{
			// set checkbox toggles
			$tpl->setCurrentBlock("tbl_action_toggle_checkboxes");
			$tpl->setVariable("JS_VARNAME","user");			
			$tpl->setVariable("JS_ONCLICK",ilUtil::array_php2js($a_user_ids));
			$tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
			$tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
			$tpl->parseCurrentBlock();
		}

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",5);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.svg"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("ilinc_header_edit_users"),"icon_usr.svg",$this->lng->txt("ilinc_header_edit_users"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("username"),
								   $this->lng->txt("firstname"),
								   $this->lng->txt("lastname"),
								   $this->lng->txt("last_visit")));
		$tbl->setHeaderVars(array("",
								  "login",
								  "firstname",
								  "lastname",
								  "last_visit"),
							array("ref_id" => $this->object->getRefId(),
								  "cmd" => $a_cmd,
								  "cmdClass" => "ilobjilinccoursegui",
								  "cmdNode" => $_GET["cmdNode"]));

		$tbl->setColumnWidth(array("","33%","33%","33%"));

		$this->__setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();
		
		$this->tpl->setVariable("SEARCH_RESULT_TABLE",$tbl->tpl->get());

		return true;
	}

	public function __showSearchRoleTable($a_result_set,$a_role_ids = NULL)
	{
		$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","searchUserForm");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("back"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","listUsersRole");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("grp_list_users"));
		$tpl->parseCurrentBlock();
		
		if (!empty($a_role_ids))
		{
			// set checkbox toggles
			$tpl->setCurrentBlock("tbl_action_toggle_checkboxes");
			$tpl->setVariable("JS_VARNAME","role");			
			$tpl->setVariable("JS_ONCLICK",ilUtil::array_php2js($a_role_ids));
			$tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
			$tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
			$tpl->parseCurrentBlock();
		}

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",5);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.svg"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("ilinc_header_edit_users"),"icon_usr_b.png",$this->lng->txt("ilinc_header_edit_users"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("obj_role"),
								   $this->lng->txt("grp_count_members")));
		$tbl->setHeaderVars(array("",
								  "title",
								  "nr_members"),
							array("ref_id" => $this->object->getRefId(),
								  "cmd" => "search",
								  "cmdClass" => "ilobjilincoursegui",
								  "cmdNode" => $_GET["cmdNode"]));

		$tbl->setColumnWidth(array("","80%","19%"));


		$this->__setTableGUIBasicData($tbl,$a_result_set,"role");
		$tbl->render();
		
		$this->tpl->setVariable("SEARCH_RESULT_TABLE",$tbl->tpl->get());

		return true;
	}

	public function __showSearchGroupTable($a_result_set,$a_grp_ids = NULL)
	{
    	$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","searchUserForm");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("back"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","listUsersGroup");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("grp_list_users"));
		$tpl->parseCurrentBlock();
		
		if (!empty($a_grp_ids))
		{
			// set checkbox toggles
			$tpl->setCurrentBlock("tbl_action_toggle_checkboxes");
			$tpl->setVariable("JS_VARNAME","group");			
			$tpl->setVariable("JS_ONCLICK",ilUtil::array_php2js($a_grp_ids));
			$tpl->setVariable("TXT_CHECKALL", $this->lng->txt("check_all"));
			$tpl->setVariable("TXT_UNCHECKALL", $this->lng->txt("uncheck_all"));
			$tpl->parseCurrentBlock();
		}

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",5);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.svg"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("ilinc_header_edit_users"),"icon_usr_b.png",$this->lng->txt("ilinc_header_edit_users"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("obj_grp"),
								   $this->lng->txt("grp_count_members")));
		$tbl->setHeaderVars(array("",
								  "title",
								  "nr_members"),
							array("ref_id" => $this->object->getRefId(),
								  "cmd" => "search",
								  "cmdClass" => "ilobjilinccoursegui",
								  "cmdNode" => $_GET["cmdNode"]));

		$tbl->setColumnWidth(array("","80%","19%"));


		$this->__setTableGUIBasicData($tbl,$a_result_set,"group");
		$tbl->render();
		
		$this->tpl->setVariable("SEARCH_RESULT_TABLE",$tbl->tpl->get());

		return true;
	}	

	public function &__initTableGUI()
	{
		include_once "./Services/Table/classes/class.ilTableGUI.php";

		return new ilTableGUI(0,false);
	}

	public function __setTableGUIBasicData(&$tbl,&$result_set,$from = "")
	{
        switch($from)
		{
			case "subscribers":
				$offset = $_GET["update_subscribers"] ? $_GET["offset"] : 0;
				$order = $_GET["update_subscribers"] ? $_GET["sort_by"] : 'login';
				$direction = $_GET["update_subscribers"] ? $_GET["sort_order"] : '';
				break;

			case "group":
				$offset = $_GET["offset"];
	           	$order = $_GET["sort_by"] ? $_GET["sort_by"] : "title";
				$direction = $_GET["sort_order"];
				break;
				
			case "role":
				$offset = $_GET["offset"];
	           	$order = $_GET["sort_by"] ? $_GET["sort_by"] : "title";
				$direction = $_GET["sort_order"];
				break;

			default:
				$offset = $_GET["offset"];
				// init sort_by (unfortunatly sort_by is preset with 'title'
	           	if ($_GET["sort_by"] == "title" or empty($_GET["sort_by"]))
                {
                    $_GET["sort_by"] = "login";
                }
                $order = $_GET["sort_by"];
				$direction = $_GET["sort_order"];
				break;
		}

		$tbl->setOrderColumn($order);
		$tbl->setOrderDirection($direction);
		$tbl->setOffset($offset);
		$tbl->setLimit($_GET["limit"]);
		//$tbl->setMaxCount(count($result_set));
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->setData($result_set);
	}
	
	public function listUsersRoleObject()
	{
		global $rbacsystem,$rbacreview;

		$_SESSION["grp_role"] = $_POST["role"] = $_POST["role"] ? $_POST["role"] : $_SESSION["grp_role"];

		// MINIMUM ACCESS LEVEL = 'administrate'
		if(!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		if(!is_array($_POST["role"]))
		{
			ilUtil::sendInfo($this->lng->txt("grp_no_roles_selected"));
			$this->searchObject();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.grp_usr_selection.html",
			"Modules/Group");
		$this->__showButton("searchUserForm",$this->lng->txt("grp_new_search"));

		// GET ALL MEMBERS
		$members = array();
		foreach($_POST["role"] as $role_id)
		{
			$members = array_merge($rbacreview->assignedUsers($role_id),$members);
		}

		$members = array_unique($members);

		// FORMAT USER DATA
		$counter = 0;
		$f_result = array();
		foreach($members as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user,false))
			{
				continue;
			}
			
			$user_ids[$counter] = $user;

			$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getLastname();
			$f_result[$counter][] = $tmp_obj->getFirstname();
			$f_result[$counter][] = ilDatePresentation::formatDate(new ilDateTime($tmp_obj->getLastLogin(),IL_CAL_DATETIME));

			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserTable($f_result,$user_ids,"listUsersRole");

		return true;
	}
	
	public function listUsersGroupObject()
	{
		global $rbacsystem,$rbacreview,$tree;

		$_SESSION["grp_group"] = $_POST["group"] = $_POST["group"] ? $_POST["group"] : $_SESSION["grp_group"];

		// MINIMUM ACCESS LEVEL = 'administrate'
		if(!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		if(!is_array($_POST["group"]))
		{
			ilUtil::sendInfo($this->lng->txt("grp_no_groups_selected"));
			$this->searchObject();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.grp_usr_selection.html",
			"Modules/Group");
		$this->__showButton("searchUserForm",$this->lng->txt("grp_new_search"));

		// GET ALL MEMBERS
		$members = array();
		foreach($_POST["group"] as $group_id)
		{
			if (!$tree->isInTree($group_id))
			{
				continue;
			}
			if (!$tmp_obj = ilObjectFactory::getInstanceByRefId($group_id))
			{
				continue;
			}

			$members = array_merge($tmp_obj->getGroupMemberIds(),$members);

			unset($tmp_obj);
		}

		$members = array_unique($members);

		// FORMAT USER DATA
		$counter = 0;
		$f_result = array();
		foreach($members as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user,false))
			{
				continue;
			}
			
			$user_ids[$counter] = $user;
			
			$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getLastname();
			$f_result[$counter][] = $tmp_obj->getFirstname();
			$f_result[$counter][] = ilDatePresentation::formatDate(new ilDateTime($tmp_obj->getLastLogin(),IL_CAL_DATETIME));

			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserTable($f_result,$user_ids,"listUsersGroup");

		return true;
	}
	
	/**
	* canceledObject is called when an operation is canceled, method links back
	* @access	public
	*/
	public function canceledObject()
	{
		$return_location = $_GET["cmd_return_location"];
		if (strcmp($return_location, "") == 0)
		{
			$return_location = "members";
		}
				
		ilUtil::sendInfo($this->lng->txt("action_aborted"),true);
		$this->ctrl->redirect($this, $return_location);
	}

	/**
	* canceledObject is called when operation is canceled, method links back
	* @access	public
	*/
	public function cancelMemberObject()
	{
		$return_location = "members";
				
		ilUtil::sendInfo($this->lng->txt("action_aborted"),true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this,$return_location,"",false,false));
	}
	
	/**
	* display group members
	*/
	public function membersObject()
	{
		global $ilAccess, $ilBench, $lng, $ilToolbar, $ilUser;
		
		if(!$this->ilias->getSetting('ilinc_active'))
		{
			$this->ilias->raiseError($lng->txt('ilinc_server_not_active'), $this->ilias->error_obj->MESSAGE);
		}
		
		$this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.icrs_members.html','Modules/ILinc');
		$this->__setSubTabs('members');
		
		$this->lng->loadLanguageModule('ilinc');
		
		// display member search button
		$is_admin = (bool)$ilAccess->checkAccess('write', '', $this->object->getRefId());		
		if($is_admin)
		{
			$ilToolbar->addButton($this->lng->txt('ilinc_add_user'), $this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start'));
		}
		
		//if current user is admin he is able to add new members to group
		$val_contact = "<img src=\"".ilUtil::getImagePath("icon_pencil_b.png")."\" alt=\"".$this->lng->txt("ilinc_mem_send_mail")."\" title=\"".$this->lng->txt("ilinc_mem_send_mail")."\" border=\"0\" vspace=\"0\"/>";
		$val_change = "<img src=\"".ilUtil::getImagePath("icon_change_b.png")."\" alt=\"".$this->lng->txt("ilinc_mem_change_status")."\" title=\"".$this->lng->txt("ilinc_mem_change_status")."\" border=\"0\" vspace=\"0\"/>";
		$val_leave = "<img src=\"".ilUtil::getImagePath("icon_group_out_b.png")."\" alt=\"".$this->lng->txt("ilinc_mem_leave")."\" title=\"".$this->lng->txt("ilinc_mem_leave")."\" border=\"0\" vspace=\"0\"/>";

		// store access checks to improve performance
		$access_leave = $ilAccess->checkAccess('leave', '', $this->object->getRefId());
		$access_write = $ilAccess->checkAccess('write', '', $this->object->getRefId());

		$member_ids = $this->object->getMemberIds();
		
		// fetch all user data in one shot to improve performance (from ILIAS db)
		$members = $this->object->getMemberData($member_ids);
		
		// fetch docent or student assignment form all coursemembers from iLinc server
		$docent_ids = $this->object->getiLincMemberIds(true);
		$student_ids = $this->object->getiLincMemberIds(false);

		$counter = 0;
		$result_set = array();
        require_once 'Services/Mail/classes/class.ilMailFormCall.php';
		foreach($members as $mem)
		{            
            $link_contact = ilMailFormCall::getLinkTarget($this, 'members', array(), array('type' => 'new', 'rcp_to' => $mem['login']));
			$link_change = $this->ctrl->getLinkTarget($this, 'changeMember').'&mem_id='.$mem['id'];		
			if(($mem['id'] == $ilUser->getId() && $access_leave) || $access_delete)
			{
				$link_leave = $this->ctrl->getLinkTarget($this, 'removeMember').'&mem_id='.$mem['id'];
			}

			//build functions
			$member_functions = '';
			if($access_write)
			{
				$member_functions = "<a href=\"$link_change\">$val_change</a>";
			}
			if(($mem['id'] == $ilUser->getId() && $access_leave) || $access_write)
			{
				$link_leave = $this->ctrl->getLinkTarget($this, 'removeMember').'&mem_id='.$mem['id'];
				$member_functions .="<a href=\"$link_leave\">$val_leave</a>";
			}
			
			// this is twice as fast than the code above
			$str_member_roles = $this->object->getMemberRolesTitle($mem['id']);
			
			if($access_write)
			{
				$result_set[$counter]['checkbox'] = ilUtil::formCheckBox(0, 'user_id[]', $mem['id']);
			}
			
			$status = $this->object->checkiLincMemberStatus($mem['ilinc_id'], $docent_ids, $student_ids);			
			if($status == ILINC_MEMBER_NOTSET)
			{
				$status = "<span class='warning'>".$this->lng->txt($status)."</span>";
			}
			else
			{
				$status = $this->lng->txt($status);
			}			
            
			$result_set[$counter]['login'] = $mem['login'];
			$result_set[$counter]['firstname'] = $mem['firstname'];
			$result_set[$counter]['lastname'] = $mem['lastname'];
			$result_set[$counter]['attending_as'] = $status;
			$result_set[$counter]['role'] = $str_member_roles;
			$result_set[$counter]['options'] = "<a href=\"$link_contact\">".$val_contact."</a>".$member_functions;

			++$counter;

			unset($member_functions);
		}
		
		include_once 'Modules/ILinc/classes/class.iliLinkMembersTableGUI.php';
		$oTable = new iliLinkMembersTableGUI($this, $result_set, 'show', 'members', 'members');		
		$oTable->setTitle($this->lng->txt('ilinc_involved_users'), 'icon_usr_b.png', $this->lng->txt('ilinc_involved_users'));
			
		return $this->tpl->setVariable('MEMBER_TABLE', $oTable->getHTML());
    }
    
	public function &executeCommand()
	{
		global $ilUser,$rbacsystem,$ilAccess,$ilErr;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->prepareOutput();

		switch($next_class)
		{
			case "ilconditionhandlergui":
				include_once './Services/AccessControl/classes/class.ilConditionHandlerGUI.php';

				if($_GET['item_id'])
				{
					$new_gui =& new ilConditionHandlerGUI($this,(int) $_GET['item_id']);
					$this->ctrl->saveParameter($this,'item_id',$_GET['item_id']);
					$this->ctrl->forwardCommand($new_gui);
				}
				else
				{
					$new_gui =& new ilConditionHandlerGUI($this);
					$this->ctrl->forwardCommand($new_gui);
				}
				break;
				
			case 'ilrepositorysearchgui':
				include_once('./Services/Search/classes/class.ilRepositorySearchGUI.php');
				$rep_search =& new ilRepositorySearchGUI();
				$rep_search->setCallback($this,'addUserObject');

				// Set tabs
				$this->tabs_gui->setTabActive('members');
				$this->ctrl->setReturn($this,'members');
				$ret =& $this->ctrl->forwardCommand($rep_search);
				$this->__setSubTabs('members');
				$this->tabs_gui->setSubTabActive('members');
				break;

			case "ilobjilincclassroomgui":
				include_once ('./Modules/ILinc/classes/class.ilObjiLincClassroomGUI.php');
				$icla_gui = new ilObjiLincClassroomGUI($_GET['class_id'],$this->ref_id);
				$ret =& $this->ctrl->forwardCommand($icla_gui);
				break;
				
			case 'ilpermissiongui':
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;
				
			case 'ilpublicuserprofilegui':
				require_once './Services/User/classes/class.ilPublicUserProfileGUI.php';
				$profile_gui = new ilPublicUserProfileGUI($_GET["user"]);
				$html = $this->ctrl->forwardCommand($profile_gui);
				$this->__setSubTabs('members');
				$this->tabs_gui->setTabActive('group_members');
				$this->tabs_gui->setSubTabActive('grp_members_gallery');
				$this->tpl->setVariable("ADM_CONTENT", $html);
				break;

			default:
				if (!$this->getCreationMode() and !$ilAccess->checkAccess('visible','',$this->object->getRefId(),'icrs'))
				{
					$ilErr->raiseError($this->lng->txt("msg_no_perm_read"),$ilErr->MESSAGE);
				}
				
				
				if(!$cmd)
				{
					$cmd = 'view';
				}
				$cmd .= 'Object';
				$this->$cmd();
				break;
		}
	}
	
	public function viewObject()
	{
		global $ilCtrl, $ilNavigationHistory, $ilAccess;

		if(!$ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt('msg_no_perm_read'), $this->ilias->error_obj->MESSAGE);
		}
		
		// add entry to navigation history
		if(!$this->getCreationMode() &&
			$ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$ilNavigationHistory->addItem($this->object->getRefId(),
				'ilias.php?baseClass=ilRepositoryGUI&cmd=view&ref_id='.$this->object->getRefId(), 'icrs');
		}
		
		if(strtolower($_GET['baseClass']) == 'iladministrationgui')
		{
			parent::viewObject();
			return true;
		}		

		return $this->renderObject();
	}	
	
	public function agendaObject()
	{
		// user login
		$url = $this->object->userLogin($this->ilias->account);
		
		if (!$url)
		{
			$this->ilias->raiseError($this->object->getErrorMsg(),$this->ilias->error_obj->FATAL);
		}

		ilUtil::redirect(trim($url));
	}
	
	public function searchUserFormObject()
	{
		global $rbacsystem;

		$this->lng->loadLanguageModule('search');

		// MINIMUM ACCESS LEVEL = 'administrate'
		if(!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		$this->tpl->addBlockFile("ADM_CONTENT","adm_content","tpl.grp_members_search.html",
			"Modules/Group");
		
		$this->tpl->setVariable("F_ACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("SEARCH_ASSIGN_USR",$this->lng->txt("grp_search_members"));
		$this->tpl->setVariable("SEARCH_SEARCH_TERM",$this->lng->txt("search_search_term"));
		$this->tpl->setVariable("SEARCH_VALUE",$_SESSION["grp_search_str"] ? $_SESSION["grp_search_str"] : "");
		$this->tpl->setVariable("SEARCH_FOR",$this->lng->txt("exc_search_for"));
		$this->tpl->setVariable("SEARCH_ROW_TXT_USER",$this->lng->txt("exc_users"));
		$this->tpl->setVariable("SEARCH_ROW_TXT_ROLE",$this->lng->txt("exc_roles"));
		$this->tpl->setVariable("SEARCH_ROW_TXT_GROUP",$this->lng->txt("exc_groups"));
		$this->tpl->setVariable("BTN2_VALUE",$this->lng->txt("cancel"));
		$this->tpl->setVariable("BTN1_VALUE",$this->lng->txt("search"));
		
        $usr = ($_POST["search_for"] == "usr" || $_POST["search_for"] == "") ? 1 : 0;
		$grp = ($_POST["search_for"] == "grp") ? 1 : 0;
		$role = ($_POST["search_for"] == "role") ? 1 : 0;

		$this->tpl->setVariable("SEARCH_ROW_CHECK_USER",ilUtil::formRadioButton($usr,"search_for","usr"));
		$this->tpl->setVariable("SEARCH_ROW_CHECK_ROLE",ilUtil::formRadioButton($role,"search_for","role"));
        $this->tpl->setVariable("SEARCH_ROW_CHECK_GROUP",ilUtil::formRadioButton($grp,"search_for","grp"));

		$this->__unsetSessionVariables();
	}
	
	function searchObject()
	{
		global $rbacsystem,$tree;

		$_SESSION["grp_search_str"] = $_POST["search_str"] = $_POST["search_str"] ? $_POST["search_str"] : $_SESSION["grp_search_str"];
		$_SESSION["grp_search_for"] = $_POST["search_for"] = $_POST["search_for"] ? $_POST["search_for"] : $_SESSION["grp_search_for"];
		
		// MINIMUM ACCESS LEVEL = 'administrate'
		if(!$rbacsystem->checkAccess("write", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		if(!isset($_POST["search_for"]) or !isset($_POST["search_str"]))
		{
			ilUtil::sendInfo($this->lng->txt("grp_search_enter_search_string"));
			$this->searchUserFormObject();
			
			return false;
		}

		if(!count($result = $this->__search(ilUtil::stripSlashes($_POST["search_str"]),$_POST["search_for"])))
		{
			ilUtil::sendInfo($this->lng->txt("grp_no_results_found"));
			$this->searchUserFormObject();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.grp_usr_selection.html",
			"Modules/Group");
		$this->__showButton("searchUserForm",$this->lng->txt("grp_new_search"));
		
		$counter = 0;
		$f_result = array();

		switch($_POST["search_for"])
		{
        	case "usr":
				foreach($result as $user)
				{
					if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user["id"],false))
					{
						continue;
					}
					
					$user_ids[$counter] = $user["id"];
					
					$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user["id"]);
					$f_result[$counter][] = $tmp_obj->getLogin();
					$f_result[$counter][] = $tmp_obj->getFirstname();
					$f_result[$counter][] = $tmp_obj->getLastname();
					$f_result[$counter][] = ilDatePresentation::formatDate(new ilDateTime($tmp_obj->getLastLogin(),IL_CAL_DATETIME));

					unset($tmp_obj);
					++$counter;
				}
				$this->__showSearchUserTable($f_result,$user_ids);

				return true;

			case "role":
				foreach($result as $role)
				{
                    // exclude anonymous role
                    if ($role["id"] == ANONYMOUS_ROLE_ID)
                    {
                        continue;
                    }

                    if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($role["id"],false))
					{
						continue;
					}
					
				    // exclude roles with no users assigned to
                    if ($tmp_obj->getCountMembers() == 0)
                    {
                        continue;
                    }
                    
                    $role_ids[$counter] = $role["id"];
                    
					$f_result[$counter][] = ilUtil::formCheckbox(0,"role[]",$role["id"]);
					$f_result[$counter][] = array($tmp_obj->getTitle(),$tmp_obj->getDescription());
					$f_result[$counter][] = $tmp_obj->getCountMembers();
					
					unset($tmp_obj);
					++$counter;
				}
				
				$this->__showSearchRoleTable($f_result,$role_ids);

				return true;
				
			case "grp":
				foreach($result as $group)
				{
					if(!$tree->isInTree($group["id"]))
					{
						continue;
					}
					
					if(!$tmp_obj = ilObjectFactory::getInstanceByRefId($group["id"],false))
					{
						continue;
					}
					
                    // exclude myself :-)
                    if ($tmp_obj->getId() == $this->object->getId())
                    {
                        continue;
                    }
                    
                    $grp_ids[$counter] = $group["id"];
                    
					$f_result[$counter][] = ilUtil::formCheckbox(0,"group[]",$group["id"]);
					$f_result[$counter][] = array($tmp_obj->getTitle(),$tmp_obj->getDescription());
					$f_result[$counter][] = $tmp_obj->getCountMembers();
					
					unset($tmp_obj);
					++$counter;
				}
				
				if(!count($f_result))
				{
					ilUtil::sendInfo($this->lng->txt("grp_no_results_found"));
					$this->searchUserFormObject();

					return false;
				}
				
				$this->__showSearchGroupTable($f_result,$grp_ids);

				return true;
		}
	}
	
	/**
	* displays confirmation formular with users that shall be assigned to group
	* @access public
	*/
	function addUserObject()
	{
		$user_ids = $_POST["user"];
		include_once 'Services/Mail/classes/class.ilMail.php';
		$mail = new ilMail($_SESSION["AccountId"]);

		if (empty($user_ids[0]))
		{
			// TODO: jumps back to grp content. go back to last search result
			$this->ilErr->raiseError($this->lng->txt("no_checkbox"),$this->ilErr->MESSAGE);
		}

		foreach ($user_ids as $new_member)
		{
			$user_obj = $this->ilias->obj_factory->getInstanceByObjId($new_member);

			if (!$this->object->addMember($user_obj,$this->object->getDefaultMemberRole(),false))
			{
				//var_dump($this->object->getErrorMsg());exit;
				$this->ilErr->raiseError($this->object->getErrorMsg(),$this->ilErr->MESSAGE);
			}
			
			$user_obj->addDesktopItem($this->object->getRefId(),"icrs");
			$mail->sendMail($user_obj->getLogin(),"","",$this->lng->txtlng("common","ilinc_mail_subj_new_subscription",$user_obj->getLanguage()).": ".$this->object->getTitle(),$this->lng->txtlng("common","ilinc_mail_body_new_subscription",$user_obj->getLanguage()),array(),array('normal'));	

			unset($user_obj);
		}
		
		//echo "end";exit;

		unset($_SESSION["saved_post"]);

		ilUtil::sendInfo($this->lng->txt("ilinc_msg_member_assigned"),true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this,"members","",false,false));
	}
	
	/**
	* displays confirmation formular with users that shall be removed from group
	* @access public
	*/
	function removeMemberObject()
	{
		global $ilUser, $rbacreview;
		
		$this->__setSubTabs('members');
		
		$user_ids = array();

		if(isset($_POST['user_id']))
		{
			$user_ids = $_POST['user_id'];
		}
		else if(isset($_GET['mem_id']))
		{
			$user_ids[] = $_GET['mem_id'];
		}
		else if(isset($_GET['mem_id_arr']))
		{
			$user_ids = explode(',', $_GET['mem_id_arr']);
		}

		if(empty($user_ids[0]))
		{
			$this->ilErr->raiseError($this->lng->txt("no_checkbox"), $this->ilErr->MESSAGE);
		}
		
		if(count($user_ids) == 1 && $ilUser->getId() != $user_ids[0])
		{
			if(!$rbacreview->isAssigned($ilUser->getId(), SYSTEM_ROLE_ID) &&
			   !in_array($ilUser->getId(), $this->object->getAdminIds()))
			{
				$this->ilErr->raiseError($this->lng->txt("ilinc_err_no_permission"), $this->ilErr->MESSAGE);
			}
		}
		
		//bool value: says if $users_ids contains current user id
		$is_dismiss_me = array_search($this->ilias->account->getId(), $user_ids);
		
		$confirm = "confirmedRemoveMember";
		$cancel  = "canceled";
		$info	 = ($is_dismiss_me !== false) ? "ilinc_dismiss_myself" : "ilinc_dismiss_member";
		$status  = "";
		$return  = "members";		
		
		ilUtil::sendQuestion($this->lng->txt($info));
		
		$data = array();
		if(is_array($user_ids))
		{
			foreach($user_ids as $id)
			{
				$obj_data = ilObjectFactory::getInstanceByObjId($id);
				$data[$id] = array(
					'type'        => ilUtil::getImageTagByType($obj_data->getType(), $this->tpl->tplPath),
					'title'       => $obj_data->getTitle(),
					'desc'        => $obj_data->getDescription(),
					'last_update' => $obj_data->getLastUpdateDate()
				);
			}
		}
		else
		{
			$obj_data = ilObjectFactory::getInstanceByObjId($user_ids);
			$data[$user_ids] = array(
				'type'        => ilUtil::getImageTagByType($obj_data->getType(), $this->tpl->tplPath),
				'title'       => $obj_data->getTitle(),
				'desc'        => $obj_data->getDescription(),
				'last_update' => $obj_data->getLastUpdateDate(),
			);
		}

		//write  in sessionvariables
		if(is_array($user_ids))
		{
			$_SESSION['saved_post']['user_id'] = $user_ids;
		}
		else
		{
			$_SESSION['saved_post']['user_id'][0] = $user_ids;
		}

		if(isset($status))
		{
			$_SESSION['saved_post']['status'] = $status;
		}	

		include_once 'Modules/ILinc/classes/class.iliLinkConfirmationTableGUI.php';
		$this->ctrl->setParameter($this, 'cmd_return_location', $a_cmd_return_location);
		$this->ctrl->setParameter($this, 'mem_id_arr', implode(',', $user_ids));
		$oTable = new iliLinkConfirmationTableGUI($this, $data, 'removeMember', $return);
		$oTable->addCommandButton($cancel, $this->lng->txt('cancel'));
		$oTable->addCommandButton($confirm, $this->lng->txt('confirm'));
		
		return $this->tpl->setContent($oTable->getHTML());					
	}

	/**
	* displays confirmation form
	* @access public
	*/
	function confirmationObject($user_id = '', $confirm = '', $cancel = '', $info = '', $status = '', $a_cmd_return_location = '', $invokeMethod = '')
	{
		
	}

	/**
	* remove members from group
	* TODO: set return location to parent object if user removes himself
	* TODO: allow user to remove himself when he is not group admin
	* @access public
	*/
	function confirmedRemoveMemberObject()
	{
		global $ilCtrl;
		
		$removed_self = false;
		include_once 'Services/Mail/classes/class.ilMail.php';
		$mail = new ilMail($_SESSION["AccountId"]);
		
		//User needs to have administrative rights to remove members...
		foreach($_SESSION["saved_post"]["user_id"] as $member_id)
		{
			$user_obj = new ilObjUser($member_id);

			if (!$this->object->removeMember($user_obj))
			{
				ilUtil::sendInfo($this->lng->txt($this->object->getErrorMsg()),true);
				ilUtil::redirect($this->ctrl->getLinkTarget($this,"members","",false,false));
			}
			
			$user_obj->dropDesktopItem($this->object->getRefId(), "icrs");
			
			if (!$removed_self and $user_obj->getId() == $this->ilias->account->getId())
			{
				$removed_self = true;
			}
			else
			{
				$mail->sendMail($user_obj->getLogin(),"","",$this->lng->txtlng("common","ilinc_mail_subj_subscription_cancelled",$user_obj->getLanguage()).": ".$this->object->getTitle(),$this->lng->txtlng("common","ilinc_mail_body_subscription_cancelled",$user_obj->getLanguage()),array(),array('normal'));
			}
		}
		
		unset($_SESSION["saved_post"]);

		ilUtil::sendInfo($this->lng->txt("ilinc_msg_membership_annulled"),true);
		
		if ($removed_self)
		{
			$ilCtrl->setParameterByClass("ilrepositorygui", "ref_id",
				$this->tree->getParentId($this->ref_id));
			$ilCtrl->redirectByClass("ilrepositorygui", "");
		}
		
		ilUtil::redirect($this->ctrl->getLinkTarget($this,"members","",false,false));
	}
	
	/**
	* displays form in which the member-status can be changed
	* @access public
	*/
	function changeMemberObject()
	{
		global $rbacreview, $ilUser;
		
		$this->__setSubTabs('members');

		$member_ids = array();

		if(isset($_POST['user_id']))
		{
			$member_ids = $_POST['user_id'];
		}
		else if(isset($_GET['mem_id']))
		{
			$member_ids[0] = $_GET['mem_id'];
		}
		else if(isset($_GET['mem_id_arr']))
		{
			$member_ids = explode(',', $_GET['mem_id_arr']);
		}

		if(empty($member_ids[0]))
		{
			$this->ilErr->raiseError($this->lng->txt('no_checkbox'), $this->ilErr->MESSAGE);
		}

		if(!$rbacreview->isAssigned($ilUser->getId(), SYSTEM_ROLE_ID) &&
		   !in_array($ilUser->getId(), $this->object->getAdminIds()))
		{
			$this->ilErr->raiseError($this->lng->txt('grp_err_no_permission'), $this->ilErr->MESSAGE);
		}

		$stati = array_flip($this->object->getLocalRoles(true));
		
		// fetch docent or student assignment form all coursemembers from iLinc server
		$docent_ids = $this->object->getiLincMemberIds(true);
		$student_ids = $this->object->getiLincMemberIds(false);
		
		$data = array();
		
		//build data structure
		foreach($member_ids as $member_id)
		{
			$member = ilObjectFactory::getInstanceByObjId($member_id);
			$mem_status = $this->object->getMemberRoles($member_id);

			include_once 'Modules/ILinc/classes/class.ilObjiLincUser.php';
			$ilinc_user = new ilObjiLincUser($member);
						
			$ilinc_status = $this->object->checkiLincMemberStatus($ilinc_user->id, $docent_ids, $student_ids);

			$docent = 0; $student = 0;

			if($ilinc_status == ILINC_MEMBER_DOCENT)
			{
				$docent = 1;
			}
			else if($ilinc_status == ILINC_MEMBER_STUDENT)
			{
				$student = 1;
			}
			
			$radio1 = ilUtil::formRadioButton($docent, 'ilinc_member_status_select['.$member->getId().']['.$ilinc_user->id.']', ILINC_MEMBER_DOCENT);
			$radio2 = ilUtil::formRadioButton($student, 'ilinc_member_status_select['.$member->getId().']['.$ilinc_user->id.']', ILINC_MEMBER_STUDENT);

			$data[$member->getId()] = array(
				'login'		=> $member->getLogin(),
				'firstname'	=> $member->getFirstname(),
				'lastname'	=> $member->getLastname(),
				'attending_as' => $radio1.' '.$this->lng->txt('ilinc_docent').'<br />'.$radio2.' '.$this->lng->txt('ilinc_student'),
				'grp_role'	=> ilUtil::formSelect($mem_status, 'member_status_select['.$member->getId().'][]', $stati, true, true, 3)
			);
		}
		
		unset($member);
		unset($ilinc_user);	
		
		include_once 'Modules/ILinc/classes/class.iliLinkMembersTableGUI.php';
		$this->ctrl->setParameter($this, 'mem_id_arr', implode(',', $member_ids));
		$oTable = new iliLinkMembersTableGUI($this, $data, 'change', 'changeMember', 'changeMember');		
		$oTable->setTitle($this->lng->txt('grp_mem_change_status'), 'icon_usr_b.png', $this->lng->txt('grp_mem_change_status'));
		
		return $this->tpl->setContent($oTable->getHTML());
	}
	
	/**
	* displays form in which the member-status can be changed
	* @access public
	*/
	public function updateMemberStatusObject()
	{
		global $ilAccess;

		if(!$ilAccess->checkAccess('write', '', $this->object->getRefId()))
		{
			$this->ilErr->raiseError('permission_denied',$this->ilErr->MESSAGE);
		}

		if(isset($_POST['member_status_select']))
		{
			foreach($_POST['member_status_select'] as $key => $value)
			{
				$this->object->leave($key);
				$this->object->join($key,$value);
			}
		}

		if(isset($_POST['ilinc_member_status_select']))
		{
			$users_to_add = array();
			$users_to_register = array();
			$users_to_unregister = array();

			foreach($_POST['ilinc_member_status_select'] as $user_id => $ilinc_arr)
			{
				$ilinc_user_id = key($ilinc_arr);
				$ilinc_user_status = current($ilinc_arr);
				//var_dump($user_id,$ilinc_arr,$ilinc_user_id,$ilinc_user_status);
				
				// if no ilinc user id was passed, there are 3 options:
				// 1. user was added by roleassignment and is registered on iLinc server
				// 2. user was added by roleassignment and is registered NOT YET on iLinc server
				// 3. iLinc server returns an empty response which happens sometimes :-(
				if($ilinc_user_id == 0)
				{
					//echo '0';
					
					// check if user is already registered on iLinc server
					$user_obj = new ilObjUser($user_id);
					
					include_once 'Modules/ILinc/classes/class.ilObjiLincUser.php';
					$ilinc_user = new ilObjiLincUser($user_obj);
					
					if(!$ilinc_user->id)
					{
						// not registered. put user on 'add list'
						$users_to_add[] =& $user_obj;
					}
					else
					{
						$users_to_register[$ilinc_user->id] = ILINC_MEMBER_STUDENT;
					}
					
					continue;
				}
				
				//echo '1';
				$users_to_unregister[] = $ilinc_user_id;
				$users_to_register[$ilinc_user_id] = $ilinc_user_status;
				//var_dump($users_to_unregister,$users_to_register);
			}
			
			if(!$this->object->unregisterUsers($users_to_unregister))
			{				
				//echo '2';
				//var_dump($this->object->getErrorMsg());exit;
				$this->ilErr->raiseError($this->object->getErrorMsg(),$this->ilErr->MESSAGE);
			}
			
			if(count($users_to_add) > 0)
			{
				//echo '3';
				foreach ($users_to_add as $user)
				{
					if (!$this->object->addUser($user))
					{
						//echo '4';
						//var_dump($this->object->getErrorMsg());exit;
						$this->ilErr->raiseError($this->object->getErrorMsg(),$this->ilErr->MESSAGE);
					}
					else
					{
						//echo '5';
						include_once 'Modules/ILinc/classes/class.ilObjiLincUser.php';
						$ilinc_user = new ilObjiLincUser($user);
						$users_to_register[$ilinc_user->id] = ILINC_MEMBER_STUDENT;
					}
				}
			}

			if(!$this->object->registerUsers($users_to_register))
			{
				//echo '6';
				//var_dump($this->object->getErrorMsg());exit;
				$this->ilErr->raiseError($this->object->getErrorMsg(),$this->ilErr->MESSAGE);
			}
		}

		ilUtil::sendInfo($this->lng->txt('msg_obj_modified'), true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, 'members', '', false, false));
	}
	
	public function editObject()
	{
		if(!$this->ilias->getSetting('ilinc_active'))
		{
			$this->ilias->raiseError($this->lng->txt('ilinc_server_not_active'), $this->ilias->error_obj->MESSAGE);
		}
		
		$this->initSettingsForm('edit');		
		$this->getObjectValues();
		return $this->tpl->setVariable('ADM_CONTENT', $this->form_gui->getHtml());	
	}
	
	protected function getObjectValues()
	{
		$this->form_gui->setValuesByArray(array(
			'title' => $this->object->getTitle(),
			'desc' => $this->object->getDescription(),
			'activated' => (int)$this->object->activated, 
			'akclassvalue1' => $this->object->getAKClassValue1(),
			'akclassvalue2' => $this->object->getAKClassValue2(),
		));
	}
	
	protected function initSettingsForm($a_mode = 'create')
	{
		include_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
		
		$this->form_gui = new ilPropertyFormGUI();
		$this->form_gui->setTableWidth('600');
		if($a_mode == 'create')
		{
			$this->form_gui->setTitle($this->lng->txt('icrs_new'));
		}		
		else
		{
			$this->form_gui->setTitle($this->lng->txt('icrs_edit'));
		}		
		$this->form_gui->setTitleIcon(ilUtil::getTypeIconPath('icrs', 0));
		
		// Title
		$text_input = new ilTextInputGUI($this->lng->txt('title'), 'title');
		$text_input->setRequired(true);
		$this->form_gui->addItem($text_input);
		
		// Description
		$text_area = new ilTextAreaInputGUI($this->lng->txt('desc'), 'desc');
		$this->form_gui->addItem($text_area);
		
		// Access
		$text_area = new ilCheckboxInputGUI($this->lng->txt('online'), 'activated');
		$this->form_gui->addItem($text_area);
		
		// AKClassValues
		if($this->ilias->getSetting('ilinc_akclassvalues_active'))
		{
			$section_header = new ilFormSectionHeaderGUI();
			$section_header->setTitle($this->lng->txt('akclassvalues'));
			$this->form_gui->addItem($section_header);			
			
			$text_input = new ilTextInputGUI($this->lng->txt('akclassvalue1'), 'akclassvalue1');
			if($this->ilias->getSetting('ilinc_akclassvalues_required'))
			{
				$text_input->setRequired(true);
			}
			$this->form_gui->addItem($text_input);
			
			$text_input = new ilTextInputGUI($this->lng->txt('akclassvalue2'), 'akclassvalue2');
			$this->form_gui->addItem($text_input);
		}
		
		if($this->call_by_reference)
		{
			$this->ctrl->setParameter($this, 'obj_id', $this->obj_id);
		}
		
		// save and cancel commands
		if($a_mode == 'create')
		{
			$this->ctrl->setParameter($this, 'mode', 'create');
			$this->ctrl->setParameter($this, 'new_type', 'icrs');
			
			$this->form_gui->addCommandButton('save', $this->lng->txt('icrs_add'));
			$this->form_gui->addCommandButton('cancel', $this->lng->txt('cancel'));
			$this->form_gui->setFormAction($this->ctrl->getFormAction($this, 'save'));
		}
		else
		{			
			$this->form_gui->addCommandButton('update', $this->lng->txt('save'));
			$this->form_gui->addCommandButton('cancel', $this->lng->txt('cancel'));
			$this->form_gui->setFormAction($this->ctrl->getFormAction($this, 'update'));
		}
	}
	
	public function joinObject()
	{
		global $ilAccess;

		if(!$ilAccess->checkAccess('join', '', (int)$_GET['ref_id']))
		{
			$this->ilias->raiseError($this->lng->txt('permission_denied'), $this->ilias->error_obj->MESSAGE);
		}

		if(!$this->object->addMember($this->ilias->account, $this->object->getDefaultMemberRole(),false))
		{
			$this->ilErr->raiseError($this->object->getErrorMsg(), $this->ilErr->MESSAGE);
		}
		
		$this->ilias->account->addDesktopItem($this->object->getRefId(), "icrs");	
		
		ilUtil::sendInfo($this->lng->txt("ilinc_msg_joined"), true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, "view", "", false, false));
	}
	
	public function isActiveAdministrationPanel()
	{
		return false;
	}
	
	public function __setSubTabs($a_tab)
	{
		global $rbacsystem, $ilUser;

		switch($a_tab)
		{
			case 'members':
				//$this->tabs_gui->addSubTabTarget('ilinc_member_administration',
				$this->tabs_gui->addSubTabTarget('members', $this->ctrl->getLinkTarget($this, 'members'), array('members', 'changeMember', 'removeMember'), get_class($this));
				$this->tabs_gui->addSubTabTarget('icrs_members_gallery', $this->ctrl->getLinkTarget($this, 'membersGallery'), 'membersGallery', get_class($this));
				$this->tabs_gui->addSubTabTarget('mail_members', $this->ctrl->getLinkTarget($this, 'mailMembers'), 'mailMembers', get_class($this));
				break;
		}
	}
	
	public function mailMembersObject()
	{
		global $rbacreview, $ilObjDataCache;
		
		include_once 'Services/AccessControl/classes/class.ilObjRole.php';
		
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.mail_members.html','Services/Contact');

		$this->__setSubTabs('members');
		
		include_once("./Services/Link/classes/class.ilLink.php");
		$link_to_seminar = ilLink::_getLink($this->object->getRefId());
		include_once 'Services/Mail/classes/class.ilMail.php';
        require_once 'Services/Mail/classes/class.ilMailFormCall.php';
		$this->tpl->setVariable("MAILACTION", ilMailFormCall::getLinkTarget($this, 'mailMembers', array(), array('type' => 'role')));
		$this->tpl->setVariable('ADDITIONAL_MESSAGE_TEXT', $link_to_seminar);		
		$this->tpl->setVariable('IMG_ARROW', ilUtil::getImagePath('arrow_downright.svg'));
		$this->tpl->setVariable('OK', $this->lng->txt('ok'));
		
		$role_ids = $rbacreview->getRolesOfRoleFolder($this->object->getRefId(), false);	
		foreach($role_ids as $role_id)
		{
			$this->tpl->setCurrentBlock('mailbox_row');
			$role_addr = $rbacreview->getRoleMailboxAddress( $role_id );
			$this->tpl->setVariable('CHECK_MAILBOX', ilUtil::formCheckbox(1, 'roles[]', htmlspecialchars($role_addr)));
			if (ilMail::_usePearMail())
			{
				// if pear mail is enabled, mailbox addresses are already localized in the language of the user
				$this->tpl->setVariable('MAILBOX', $role_addr);
			}
			else
			{
				// if pear mail is not enabled, we need to localize mailbox addresses in the language of the user
				$this->tpl->setVariable('MAILBOX', ilObjRole::_getTranslation( $ilObjDataCache->lookupTitle( $role_id ) ). ' (' . $role_addr . ')');
			}

			$this->tpl->parseCurrentBlock();
		}
	}
	
	/**
	 * Builds a learnlink seminar members gallery as a layer of left-floating images
	 * @author Arturo Gonzalez <arturogf@gmail.com>
	 * @access       public
	 */
	public function membersGalleryObject()
	{ 
		global $rbacsystem;
	    
		$is_admin = (bool) $rbacsystem->checkAccess('write', $this->object->getRefId());
	    
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.icrs_members_gallery.html','Modules/ILinc');
	    
		$this->__setSubTabs('members');
		
		$this->lng->loadLanguageModule('ilinc');

		$member_ids = $this->object->getMemberIds();
		
		// fetch all user data in one shot to improve performance (from ILIAS db)
		$members = $this->object->getMemberData($member_ids);
		
		// fetch docent or student assignment form all coursemembers from iLinc server
		$admin_ids = $this->object->getiLincMemberIds(true);

	    // MEMBERS
	    if(count($members))
	    {
			foreach($members as $member)
			{
				// get user object
				if(!($usr_obj = ilObjectFactory::getInstanceByObjId($member['id'],false)))
				{
					continue;
				}
				
				$public_profile = $usr_obj->getPref('public_profile');

				// SET LINK TARGET FOR USER PROFILE
				$this->ctrl->setParameterByClass('ilpublicuserprofilegui', 'user', $member['id']);
				$profile_target = $this->ctrl->getLinkTargetByClass('ilpublicuserprofilegui','getHTML');
			
				// GET USER IMAGE
				$file = $usr_obj->getPersonalPicturePath('xsmall');
			    
				switch(in_array($member['ilinc_id'],$admin_ids))
				{
					//admins
					case 1:
						if($public_profile == 'y' || $public_profile == 'g')
						{
							$this->tpl->setCurrentBlock('tutor_linked');
							$this->tpl->setVariable('LINK_PROFILE', $profile_target);
							$this->tpl->setVariable('SRC_USR_IMAGE', $file);
							$this->tpl->parseCurrentBlock();
						}
						else
						{
							$this->tpl->setCurrentBlock('tutor_not_linked');
							$this->tpl->setVariable('SRC_USR_IMAGE', $file);
							$this->tpl->parseCurrentBlock();
						}
						$this->tpl->setCurrentBlock('tutor');
						break;
				
					case 0:
						if($public_profile == 'y' || $public_profile == 'g')
						{
							$this->tpl->setCurrentBlock('member_linked');
							$this->tpl->setVariable('LINK_PROFILE', $profile_target);
							$this->tpl->setVariable('SRC_USR_IMAGE', $file);
							$this->tpl->parseCurrentBlock();
						}
						else
						{
							$this->tpl->setCurrentBlock('member_not_linked');
							$this->tpl->setVariable('SRC_USR_IMAGE', $file);
							$this->tpl->parseCurrentBlock();
						}
						$this->tpl->setCurrentBlock('member');
						break;
				}
				
				// do not show name, if public profile is not activated
				if($public_profile == 'y' || $public_profile == 'g')
				{
					$this->tpl->setVariable('FIRSTNAME', $member['firstname']);
					$this->tpl->setVariable('LASTNAME', $member['lastname']);
				}
				
				$this->tpl->setVariable('LOGIN', $usr_obj->getLogin());
				$this->tpl->parseCurrentBlock();
			}
			
			$this->tpl->setCurrentBlock('members');	
			$this->tpl->setVariable('MEMBERS_TABLE_HEADER',$this->lng->txt('ilinc_involved_users'));
			$this->tpl->parseCurrentBlock();
		}
	    
	    $this->tpl->setVariable('TITLE', $this->lng->txt('icrs_members_print_title'));
	    $this->tpl->setVariable('CSS_PATH', ilUtil::getStyleSheetLocation());
	    
	    $headline = $this->object->getTitle().'<br />'.$this->object->getDescription();
	    $this->tpl->setVariable('HEADLINE', $headline);
	    
	    $this->tpl->show();
	    exit();
	}
	
	public function addStandardContainerSubTabs()
	{
		
	}
	
	public function showAdministrationPanel($tpl)	
	{
	}
	
	public static function _goto($a_target)
	{
		global $ilAccess, $ilErr, $lng;

		if ($ilAccess->checkAccess('read', '', $a_target))
		{
			ilObjectGUI::_gotoRepositoryNode($a_target);
		}
		else
		{
			$ilErr->raiseError($lng->txt('msg_no_perm_read'), $ilErr->FATAL);
		}
	}
}
?>
