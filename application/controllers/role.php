<?php
require 'baseController.php';

class Role extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('role');
        parent::__construct();
    }
	
}
?>