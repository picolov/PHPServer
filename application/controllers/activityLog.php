<?php
require 'baseController.php';

class ActivityLog extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('activityLog');
        parent::__construct();
    }
    
    /**
     * Override Get a List of activityLog for current User
     * @return multitype:unknown
     */
    function listRecentMine_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array("user" => $this->token['user']['_id']));
    	$itemCursor->sort(array('time' => -1));
    	$itemCursor->limit(5);
    	foreach ($itemCursor as $item) {
    		$item['user'] = $this->token['user'];
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Override Get a List of activityLog for current User
     * @return multitype:unknown
     */
    function listMine_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array("user" => $this->token['user']['_id']));
    	$itemCursor->sort(array('time' => -1));
    	$itemCursor->limit(200);
    	foreach ($itemCursor as $item) {
    		$item['user'] = $this->token['user'];
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Override Get a List of activityLog for User
     * @return multitype:unknown
     */
    function listByUser_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$userModel = $this->db->selectCollection('user');
    	$user = $userModel->findOne(array("_id" => $this->get('_id')));
    	$userInfo = ['_id' => $user['_id'], 'username' => $user['username'], 'role' => $user['role'], 'firstName' => $user['firstName'], 'middleName' => $user['middleName'], 'lastName' => $user['lastName']];
    	$itemCursor = $itemModel->find(array("user" => $this->get('_id')));
    	$itemCursor->sort(array('time' => -1));
    	$itemCursor->limit(200);
    	foreach ($itemCursor as $item) {
    		$item['user'] = $userInfo;
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>