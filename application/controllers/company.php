<?php
require 'baseController.php';

class Company extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('Company');
    	$this->setObjMap(array(
    		'parentCompany' => 'Company',
			'status' => 'StatusCompany',
			'business' => 'BusinessType',
    		'type' => 'TypeCompany',
    		'territory' => 'TerritoryCompany'
		));
        parent::__construct();
    }
	
}
?>