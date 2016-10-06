<?php
require 'baseController.php';

class OutgoingToolsLeakBaker extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OutgoingToolsLeakBaker');
    	$this->setObjMap(array(
    			
    	));
        parent::__construct();
    }
    
    /**
     * Get all List of leak baker with Job detail
     * @return multitype:unknown
     */
    function listWithJob_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find();
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			if ($key == 'job') {
    				$jobModel = $this->db->selectCollection('Job');
    				$job = $jobModel->findOne(array('_id' => $item[$key]));
    				$item[$key] = $job;
    			} else {
	    			$prop = $item[$key];
	    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
	    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
	    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
	    				$item[$key] = $innerItem;
	    			}
    			}
    		}
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get a List of leak baker by Job id
     * @return multitype:unknown
     */
    
    function listByJob_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array('job' => $this->get('_id')));
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>