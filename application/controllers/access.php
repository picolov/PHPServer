<?php
require 'baseController.php';

class Access extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('access');
        parent::__construct();
    }
    
    /**
     * Override Get a List of Access for current User
     * @return multitype:unknown
     */
    function listMine_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array("role" => array('$in' => $this->token['user']['role'])));
    	foreach ($itemCursor as $item) {
    		$data[] = '@' . $item['_id'];
    		$data = array_merge($data,$item['url']);
    	}
    	$data = array_values(array_unique($data));
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get a List of Access based by Role
     * @return multitype:unknown
     */
    function listByRole_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array("role" => array('$in' => [$this->get("_id")] )));
    	foreach ($itemCursor as $item) {
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Add Role to Access
     * @return multitype:unknown
     */
    function addRole_post() {
    	$data = $this->_post_args;
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array("_id" => $data["_id"]));
    	if (!in_array($data['role'], $item['role'])) {
    		$item['role'][] = $data['role'];
    		$itemModel->update(array("_id" => $data['_id']), $item);
    	} 
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Remove Role from Access
     * @return multitype:unknown
     */
    function removeRole_post() {
    	$data = $this->_post_args;
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array("_id" => $data["_id"]));
    	if (in_array($data['role'], $item['role'])) {
    		$pos = array_search($data['role'], $item['role']);
    		unset($item['role'][$pos]);
    		$item['role'] = array_values($item['role']);
    		$itemModel->update(array("_id" => $data['_id']), $item);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>