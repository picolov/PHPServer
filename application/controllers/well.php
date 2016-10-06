<?php
require 'baseController.php';

class Well extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('Well');
        parent::__construct();
    }
}
?>