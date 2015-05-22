<?php
require_once("./Services/UICore/classes/class.ilTemplate.php");

/**
 * Class ilAsyncOutputHandler
 * @author Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class ilAsyncOutputHandler {
	const OUTPUT_MODAL = "output_modal";
	const OUTPUT_EMPTY = "output_empty";

	protected $content;

	protected $heading;

	protected $window_properties;

	public function __construct($content = null, $heading = null, $windows_properties = array()) {
		$this->content = $content;
		$this->heading = $heading;

		$this->window_properties = $windows_properties;
	}


	/**
	 * Output content in different ways
	 * self::OUTPUT_MODAL: Output as bootstrap modal
	 * self::OUTPUT_EMPTY: Only content without ILIAS-layout
	 *
	 * @param string $type
	 */
	public function terminate($type = self::OUTPUT_MODAL) {
		if($type == self::OUTPUT_MODAL) {
			$tpl = new ilTemplate('tpl.modal_content.html', false, false, 'Modules/TrainingProgramme');
			$tpl->setVariable('HEADING', $this->getHeading());
			$tpl->setVariable('BODY', $this->getContent());

			/*foreach($this->window_properties as $key => $value) {
				if($value) {
					$tpl->activeBlock($key);
				} else {
					$tpl->removeBlockData($key);
				}
			}*/

			echo $tpl->get();
			exit();

		} else if($type == self::OUTPUT_EMPTY) {

			echo $this->getContent();
			exit();
		}
	}


	/**
	 * Encode data as json for async output
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public static function encodeAsyncResponse(array $data = array()) {
		global $ilCtrl;

		$data['cmd'] = $ilCtrl->getCmd();

		return json_encode($data);
	}


	/**
	 * Handles async output
	 * @param      $normal_content
	 * @param null $async_content
	 * @param bool $apply_to_tpl
	 *
	 * @return null
	 */
	public static function handleAsyncOutput($normal_content, $async_content = null, $apply_to_tpl = true) {
		global $ilCtrl, $tpl;

		$content = ($ilCtrl->isAsynch() && $async_content != null)? $async_content : $normal_content;

		if($ilCtrl->isAsynch()) {
			echo $content;
			exit();
		} else {
			if($apply_to_tpl) {
				$tpl->setContent($content);
			} else {
				return $content;
			}
		}
	}

	/**
	 * @return mixed
	 */
	public function getContent() {
		return $this->content;
	}


	/**
	 * @param mixed $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}


	/**
	 * @return mixed
	 */
	public function getHeading() {
		return $this->heading;
	}


	/**
	 * @param mixed $heading
	 */
	public function setHeading($heading) {
		$this->heading = $heading;
	}


	/**
	 * @return mixed
	 */
	public function getWindowProperties() {
		return $this->window_properties;
	}


	/**
	 * @param mixed $window_properties
	 */
	public function setWindowProperties($window_properties) {
		$this->window_properties = $window_properties;
	}

}