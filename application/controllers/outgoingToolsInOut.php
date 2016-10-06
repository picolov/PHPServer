<?php
require 'baseController.php';

class OutgoingToolsInOut extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OutgoingToolsInOut');
    	$this->setObjMap(array(
    	));
        parent::__construct();
    }
    
    /**
     * Get all List of In Out with Job detail and Service Order detail
     * @return multitype:unknown
     */
    function listWithJobAndSvo_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	if ($this->get('from') && $this->get('to')) {
    		$itemCursor = $itemModel->find(array("date" => array('$gt' => (int) $this->get('from'), '$lte' => (int) $this->get('to'))));
    	} else {
    		$itemCursor = $itemModel->find();
    	}
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			if ($key == 'job') {
    				$jobId = $item[$key];
    				$jobModel = $this->db->selectCollection('Job');
    				$job = $jobModel->findOne(array('_id' => $jobId));
    				$item[$key] = $job;
    				$svoModel = $this->db->selectCollection('ServiceOrder');
    				$svo = $svoModel->findOne(array('job' => $jobId));
    				$item['svo'] = $svo;
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
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'from' => $this->get('from'), 'to' => $this->get('to')];
    	return $output;
    }
    
    /**
     * Get all List of In Out with Job detail
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
     * Get a List of In Out by Job id
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