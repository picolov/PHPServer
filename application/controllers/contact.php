<?php
require 'baseController.php';

class Contact extends BaseController {
	
	public function __construct()
	{
		$this->setDoc('Contact');
		$this->setObjMap(array(
			'company' => 'Company',
			'reportTo' => 'Contact'
		));
		parent::__construct();
	}
	
}
?>