<?php
require 'baseController.php';
require_once APPPATH . "/libraries/Unirest.php";

class Glossary extends BaseController {

    public function __construct()
    {
        $this->setDoc('Glossary');
        parent::__construct();
    }

    /**
     * Override Create Glossary
     * @return multitype:unknown
     */
    function save_post() {
        $data = $this->_post_args;
        if (is_array($data)) {
            $con = new MongoClient ();
            $this->db = $con->selectDB(_DB_NAME);
            $itemModel = $this->db->selectCollection($this->docName);
            if (!array_key_exists('_id', $data)) {
                $data['_id'] = UUID::v4();
            }
            $itemModel->save($data);
            $output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
            return $output;
        }
    }

    function delete_post(){
        $data = $this->_post_args;
        if (is_array($data)) {
            $con = new MongoClient ();
            $this->db = $con->selectDB(_DB_NAME);
            $itemModel = $this->db->selectCollection($this->docName);
            if (array_key_exists('_id', $data)) {
                $itemModel->remove($data);
                $output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
            } else{
                $output = ['status' => 401, 'message' => 'No IDs', 'data' => $data];
            }
            return $output;
        }
    }
}

?>