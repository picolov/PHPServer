<?php
require 'baseController.php';

class Manual extends BaseController {

    public function __construct()
    {
        $this->setDoc('Manual');
        parent::__construct();
    }
    
    /**
     * Update operation manual filename
     * @return multitype:unknown
     */
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	if (is_array($data) && array_key_exists('_id', $data)) {
    		$data['updatedBy'] = $this->token['user']['username'];
    		$data['updatedTime'] = time();
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
    		$name = $dataModified['name'];
    		foreach ($data as $key => $value) {
    			$dataModified[$key] = $value;
    		}
    		rename("./uploads/operationManual/" . $name, "./uploads/operationManual/" . $data['name']);
    		$dataModified['url'] = "/uploads/operationManual/" . $data['name'];
    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}

?>