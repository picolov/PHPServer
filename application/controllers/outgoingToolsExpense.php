<?php
require 'baseController.php';

class OutgoingToolsExpense extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OutgoingToolsExpense');
    	$this->setObjMap(array(
    	));
        parent::__construct();
    }
    
    /**
     * Get all My List of Transfer with Job detail
     * @return multitype:unknown
     */
    function listMineWithJob_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array("user" => $this->token['user']['_id']));
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
     * Get all List of Transfer with Job detail
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
     * Get a List of Transfer by Job id
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
    
    /**
     * Get a Detail of Service Order by Job id
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