<?php
require 'baseController.php';

class PriceManager extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('PriceManager');
        parent::__construct();
    }
}
?>