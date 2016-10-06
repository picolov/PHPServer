<?php
require 'baseController.php';

class OutgoingToolsTransfer extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OutgoingToolsTransfer');
    	$this->setObjMap(array(
    	));
        parent::__construct();
    }
    
    /**
     * Create Equipment Transfer and send notification
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
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$itemModel->save($data);
    		if (array_key_exists('notif', $data) && $data['notif'] && $data['notif'] == '1') {
	    		$email_to = $data['notifMail'];
	    		$email_subject = 'Equipment Transfer : ' . $data['status'];
	    		$email_message = "Hi " . $data['customer'] . ", \r\n\r\nAn Equipment Transfer is happen";
	    		
	    		// create email headers
	    		$headers = 'From: admin@isiaws.radmond.com'."\r\n".
	    				'Reply-To: admin@isiaws.radmond.com'."\r\n" .
	    				'X-Mailer: PHP/' . phpversion();
	    		$result = mail($email_to, $email_subject, $email_message, $headers);
    		}
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Update Equipment Transfer and send notification if status is different
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
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
    		$statusChanged = false;
    		if (array_key_exists('status', $dataModified) && array_key_exists('status', $data)) {
    			$statusChanged = ($dataModified['status'] != $data['status']);
    		}
    		foreach ($data as $key => $value) {
    			$dataModified[$key] = $value;
    		}
    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
    		if (array_key_exists('notif', $data) && $data['notif'] && $data['notif'] == '1' && $statusChanged) {
    			$email_to = $data['notifMail'];
    			$email_subject = 'Equipment Transfer : ' . $data['status'];
    			$email_message = "Hi " . $data['customer'] . ", \r\n\r\nAn Equipment Transfer is happen";
    			 
    			// create email headers
    			$headers = 'From: admin@isiaws.radmond.com'."\r\n".
    					'Reply-To: admin@isiaws.radmond.com'."\r\n" .
    					'X-Mailer: PHP/' . phpversion();
    			$result = mail($email_to, $email_subject, $email_message, $headers);
    		}
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