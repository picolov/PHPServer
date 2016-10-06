<?php
require 'baseController.php';

class JobSheet extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('JobSheet');
        parent::__construct();
    }

    /**
     * Get Maximum Line Number for Job SheetData
     * @return multitype:unknown
     */
    function maxLine_get() {
        $data = [];
        $con = new MongoClient ();
        $this->db = $con->selectDB(_DB_NAME);
        $itemModel = $this->db->selectCollection($this->docName);
        $itemCursor = $itemModel->find()->sort(array('A' => -1))->limit(1);
        foreach ($itemCursor as $item) {
            $data[] = $item;
        }
        $output = ['status' => 1, 'message' => 'successfuly queried', 'data' => $data];
        return $output;
    }
}
?>