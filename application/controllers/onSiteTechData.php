<?php
require 'baseController.php';

class OnSiteTechData extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OnSiteTechData');
    	$this->setObjMap(array(
    			'companyOrdered' => 'Company',
    			'companyName' => 'Company',
    			'additionalCompany' => 'Company',
				'region' => 'Region',
    			'coman_' => 'Contact',
    			'geo_' => 'Contact',
    			'engineer_' => 'Contact',
				'other_' => 'Contact',
    			'manager' => 'Contact',
    			'engineer' => 'Contact',
    			'geologist' => 'Contact'
    	));
        parent::__construct();
    }
    
    /**
     * Get a Detail of OnSiteTechData by Job id
     * @return multitype:unknown
     */
    function detailByJob_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array('job' => $this->get('_id')));
    	if ($item) {
	    	foreach($item as $key => $value) {
	    		$prop = $item[$key];
	    		if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
	    			$innerItemModel = $this->db->selectCollection($prop['$ref']);
	    			$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
	    			$item[$key] = $innerItem;
	    		}
	    	}
    	}
    	$data[] = $item;
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>