<?php
require 'baseController.php';

class Profile extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('profile');
        parent::__construct();
    }
	
    /**
     * Get Profile current User
     * @return multitype:unknown
     */
    function getMine_get() {
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array("user" => $this->token['user']['_id']));
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $item];
    	return $output;
    }
    
    /**
     * Save or Update Current User Profile
     * @return multitype:unknown
     */
    function saveMine_post() {
    	$data = $this->_post_args;
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array("user" => $this->token['user']['_id']));
    	if ($item) {
    		// update existing
    		$data['updatedBy'] = $this->token['user']['username'];
    		$data['updatedTime'] = time();
    		if (array_key_exists('isUsingNickName', $data) && $data['isUsingNickName'] === true) {
    			$data['employeeName'] = $data['nickName'] . ' ' . $data['lastName'];
    		} else {
    			$data['isUsingNickName'] = false;
    			$data['employeeName'] = $data['firstName'] . ' ' . $data['lastName'];
    		}
    		foreach ($data as $key => $value) {
    			$item[$key] = $value;
    		}
    		$itemModel->update(array("user" => $this->token['user']['_id']), $item);
    	} else {
    		// insert new
    		$data['_id'] = UUID::v4();
    		$data['createdBy'] = $this->token['user']['username'];
    		$data['createdTime'] = time();
    		$data['user'] = $this->token['user']['_id'];
    		if (array_key_exists('isUsingNickName', $data) && $data['isUsingNickName'] === true) {
    			$data['employeeName'] = $data['nickName'] . ' ' . $data['lastName'];
    		} else {
    			$data['isUsingNickName'] = false;
    			$data['employeeName'] = $data['firstName'] . ' ' . $data['lastName'];
    		}
    		$itemModel->save($data);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Create profile
     * @return multitype:unknown
     */
    function save_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	if (is_array($data)) {
    		if (!array_key_exists('_id', $data)) {
    			$data['_id'] = UUID::v4();
    		}
    		foreach ($data as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $this->objMap)) {
    					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
    				} else {
    					$data[$key] = $value['_id'];
    				}
    			}
    		}
    		$data['createdBy'] = $this->token['user']['username'];
    		$data['createdTime'] = time();
    		if (array_key_exists('isUsingNickName', $data) && $data['isUsingNickName'] === true) {
    			$data['employeeName'] = $data['nickName'] . ' ' . $data['lastName'];
    		} else {
    			$data['isUsingNickName'] = false;
    			$data['employeeName'] = $data['firstName'] . ' ' . $data['lastName'];
    		}
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$itemModel->save($data);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Update profile
     * @return multitype:unknown
     */
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	if (is_array($data) && array_key_exists('_id', $data)) {
    		foreach ($data as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $this->objMap)) {
    					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
    				} else {
    					$data[$key] = $value['_id'];
    				}
    			}
    		}
    		$data['updatedBy'] = $this->token['user']['username'];
    		$data['updatedTime'] = time();
    		if (array_key_exists('isUsingNickName', $data) && $data['isUsingNickName'] === true) {
    			$data['employeeName'] = $data['nickName'] . ' ' . $data['lastName'];
    		} else {
    			$data['isUsingNickName'] = false;
    			$data['employeeName'] = $data['firstName'] . ' ' . $data['lastName'];
    		}
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
    		foreach ($data as $key => $value) {
    			$dataModified[$key] = $value;
    		}
    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get a Detail of Profile by User
     * @return multitype:unknown
     */
    function detailByUser_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array('user' => $this->get('_id')));
    	foreach($item as $key => $value) {
    		$prop = $item[$key];
    		if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    			$innerItemModel = $this->db->selectCollection($prop['$ref']);
    			$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    			$item[$key] = $innerItem;
    		}
    	}
    	$data[] = $item;
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get a Detail Profile, and also his active jobs (this._id == Job.technician.$id)
     * @return multitype:unknown
     */
    function detail_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array('_id' => $this->get('_id')));
    	foreach($item as $key => $value) {
    		$prop = $item[$key];
    		if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    			$innerItemModel = $this->db->selectCollection($prop['$ref']);
    			$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    			$item[$key] = $innerItem;
    		}
    	}
    	$jobModel = $this->db->selectCollection('Job');
    	$jobCursor = $jobModel->find(array('$or' => array(
    			array('technician' => $item['employeeName']),
    			array('technician2' => $item['employeeName'])
    	)));
    	$item['activeJobs'] = [];
    	foreach ($jobCursor as $job) {
	    	foreach($job as $key2 => $value2) {
	    		$prop2 = $job[$key2];
	    		if (is_array($prop2) && array_key_exists('$ref', $prop2) && array_key_exists('$id', $prop2)) {
	    			$innerItemModel2 = $this->db->selectCollection($prop2['$ref']);
	    			$innerItem2 = $innerItemModel2->findOne(array('_id' => $prop2['$id']));
	    			$job[$key2] = $innerItem2;
	    		}
	    	}
    		$item['activeJobs'][] = $job; 
    	}
    	$data[] = $item;
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>