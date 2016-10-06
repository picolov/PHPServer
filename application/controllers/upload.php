<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/UUID.php';

class upload extends CI_Controller {
	
	public function forum() {
		// Set upload config vars
		$config['upload_path']   = './uploads/forum/';
		$config['allowed_types'] = 'gif|jpg|png|jpeg|pdf';
		$config['overwrite'] = TRUE;
		$config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
		 
		$this->upload_uri = '/uploads/forum/';
		$this->load->library('upload', $config);
		$this->output->enable_profiler(FALSE);
		// If it is a upload POST request
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			// Attempt upload
			if ( ! $this->upload->do_upload('file')) {
				// Return any errors
				$status['done']  = 0;
				$status['message'] = $this->upload->display_errors();
				$data = json_encode($status);
			} else {
				// Upload success
				$upload = array('upload_data' => $this->upload->data());
				
				$status['done']  = 1;
				$status['width'] = $upload['upload_data']['image_width'];
				$status['url']   = $this->upload_uri . $upload['upload_data']['file_name'];
				
				$data = json_encode($status);
			}
			// Else if it is a check for progress GET request, return 'no progress'
		} else if(isset($_GET['check'])) {
			$status['noprogress'] = true;
			$data = json_encode($status);
		}
		return $this->output->set_output($data);
	}
    public function operationManual() {
    	// Set upload config vars
    	$config['upload_path']   = './uploads/operationManual/';
    	$config['allowed_types'] = '*';
    	$config['overwrite'] = TRUE;
    	$config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
    	
    	$this->upload_uri = '/uploads/operationManual/';
    	$this->load->library('upload', $config);
    	$this->output->enable_profiler(FALSE);
        // If it is a upload POST request
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            // Attempt upload
            if ( ! $this->upload->do_upload('file')) {
                // Return any errors
            	$status['done']  = 0;
            	$status['message'] = $this->upload->display_errors();
                $data = json_encode($status);
            } else {
                // Upload success
    			$upload = array('upload_data' => $this->upload->data());
    			$status['done']  = 1;
    			$status['url']   = $this->upload_uri.'/'.$upload['upload_data']['file_name'];
    			$con = new MongoClient ();
    			$this->db = $con->selectDB(_DB_NAME);
    			$itemModel = $this->db->selectCollection('Manual');
    			$manual['_id'] = UUID::v4();
    			$manual['name'] = $upload['upload_data']['file_name'];
    			$manual['size'] = $upload['upload_data']['file_size'] . " KB";
    			$manual['uploadDate'] = time();
    			$manual['url'] = $this->upload_uri.'/'.$upload['upload_data']['file_name'];
    			$itemModel->save($manual);
    			
    			$data = json_encode($status);
            }
        // Else if it is a check for progress GET request, return 'no progress'
        } else if(isset($_GET['check'])) {
            $status['noprogress'] = true;
			$data = json_encode($status);
        }
        return $this->output->set_output($data);
    }
    
    public function jsea() {
    	// Set upload config vars
    	$config['upload_path']   = './uploads/jsea/';
    	$config['allowed_types'] = 'doc|docx|xls|xlsx|pdf';
    	$config['overwrite'] = TRUE;
    	$config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
    	$config['max_width']     = '1024';
    	$config['max_height']    = '768';
    	$config['file_name'] = uniqid();;
    	 
    	$this->upload_uri = '/uploads/jsea/';
    	$this->load->library('upload', $config);
    	$this->output->enable_profiler(FALSE);
    	// If it is a upload POST request
    	if ($this->input->server('REQUEST_METHOD') == 'POST') {
    		// Attempt upload
    		if ( ! $this->upload->do_upload('file')) {
    			// Return any errors
    			$status['done']  = 0;
    			$status['message'] = $this->upload->display_errors();
    			$data = json_encode($status);
    		} else {
    			// Upload success
    			$upload = array('upload_data' => $this->upload->data());
    			$status['done']  = 1;
    			$status['width'] = $upload['upload_data']['image_width'];
    			$status['url']   = $this->upload_uri.$upload['upload_data']['file_name'];
    			$con = new MongoClient ();
    			$this->db = $con->selectDB(_DB_NAME);
    			$itemModel = $this->db->selectCollection('OnSiteJSEA');
    			$jsea = $itemModel->findOne(array('_id' => $_REQUEST['id']));
    			$jsea['uploadedFile'] = $this->upload_uri.$upload['upload_data']['file_name'];
    			$itemModel->update(array("_id" => $jsea['_id']), $jsea);
    			 
    			$data = json_encode($status);
    		}
    		// Else if it is a check for progress GET request, return 'no progress'
    	} else if(isset($_GET['check'])) {
    		$status['noprogress'] = true;
    		$data = json_encode($status);
    	}
    	return $this->output->set_output($data);
    }
    
    public function calibration() {
    	// Set upload config vars
    	$config['upload_path']   = './uploads/calibration/';
    	$config['allowed_types'] = 'doc|docx|xls|xlsx|pdf';
    	$config['overwrite'] = TRUE;
    	$config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
    	$config['max_width']     = '1024';
    	$config['max_height']    = '768';
    	$config['file_name'] = uniqid();;
    
    	$this->upload_uri = '/uploads/calibration/';
    	$this->load->library('upload', $config);
    	$this->output->enable_profiler(FALSE);
    	// If it is a upload POST request
    	if ($this->input->server('REQUEST_METHOD') == 'POST') {
    		// Attempt upload
    		if ( ! $this->upload->do_upload('file')) {
    			// Return any errors
    			$status['done']  = 0;
    			$status['message'] = $this->upload->display_errors();
    			$data = json_encode($status);
    		} else {
    			// Upload success
    			$upload = array('upload_data' => $this->upload->data());
    			$status['done']  = 1;
    			$status['width'] = $upload['upload_data']['image_width'];
    			$status['url']   = $this->upload_uri.$upload['upload_data']['file_name'];
    			$con = new MongoClient ();
    			$this->db = $con->selectDB(_DB_NAME);
    			$itemModel = $this->db->selectCollection('ServiceOrder');
    			$svo = $itemModel->findOne(array('job' => $_REQUEST['id']));
    			$svo['serial' . $_REQUEST['num'] . 'File'] = $this->upload_uri.$upload['upload_data']['file_name'];
    			$svo['serial' . $_REQUEST['num'] . 'Name'] = $upload['upload_data']['file_name'];
    			$svo['serial' . $_REQUEST['num'] . 'Size'] = $upload['upload_data']['file_size'] . " KB";
    			$svo['serial' . $_REQUEST['num'] . 'Date'] = time();
    			$itemModel->update(array("_id" => $svo['_id']), $svo);
    
    			$data = json_encode($status);
    		}
    		// Else if it is a check for progress GET request, return 'no progress'
    	} else if(isset($_GET['check'])) {
    		$status['noprogress'] = true;
    		$data = json_encode($status);
    	}
    	return $this->output->set_output($data);
    }
    
    public function job() {
    	// Set upload config vars
    	$config['upload_path']   = './uploads/job/';
    	$config['allowed_types'] = '*';
    	$config['overwrite'] = TRUE;
    	$config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
    	$config['file_name'] = uniqid();
    	$filename = $_FILES['file']['name'];
    	$this->upload_uri = '/uploads/job/';
    	$this->load->library('upload', $config);
    	$this->output->enable_profiler(FALSE);
    	// If it is a upload POST request
    	if ($this->input->server('REQUEST_METHOD') == 'POST') {
    		// Attempt upload
    		if ( ! $this->upload->do_upload('file')) {
    			// Return any errors
    			$status['done']  = 0;
    			$status['message'] = $this->upload->display_errors();
    			$data = json_encode($status);
    		} else {
    			// Upload success
    			$upload = array('upload_data' => $this->upload->data());
    			$status['done']  = 1;
    			$status['width'] = $upload['upload_data']['image_width'];
    			$status['url']   = $this->upload_uri.$upload['upload_data']['file_name'];
    			$status['name'] = $filename;
    			$status['size'] = $upload['upload_data']['file_size'] . " KB";
    			$status['uploadDate'] = time();
    			$con = new MongoClient ();
    			$this->db = $con->selectDB(_DB_NAME);
    
    			$data = json_encode($status);
    		}
    		// Else if it is a check for progress GET request, return 'no progress'
    	} else if(isset($_GET['check'])) {
    		$status['noprogress'] = true;
    		$data = json_encode($status);
    	}
    	return $this->output->set_output($data);
    }
    
    public function jobFromDetail() {
    	// Set upload config vars
    	$config['upload_path']   = './uploads/job/';
    	$config['allowed_types'] = '*';
    	$config['overwrite'] = TRUE;
    	$config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
    	$config['file_name'] = uniqid();
    	$filename = $_FILES['file']['name'];
    
    	$this->upload_uri = '/uploads/job/';
    	$this->load->library('upload', $config);
    	$this->output->enable_profiler(FALSE);
    	// If it is a upload POST request
    	if ($this->input->server('REQUEST_METHOD') == 'POST') {
    		// Attempt upload
    		if ( ! $this->upload->do_upload('file')) {
    			// Return any errors
    			$status['done']  = 0;
    			$status['message'] = $this->upload->display_errors();
    			$data = json_encode($status);
    		} else {
    			// Upload success
    			$upload = array('upload_data' => $this->upload->data());
    			$status['done']  = 1;
    			$status['width'] = $upload['upload_data']['image_width'];
    			$status['url']   = $this->upload_uri.$upload['upload_data']['file_name'];
    			$status['name'] = $filename;
    			$status['size'] = $upload['upload_data']['file_size'] . " KB";
    			$status['uploadDate'] = time();
    			$con = new MongoClient ();
    			$this->db = $con->selectDB(_DB_NAME);
    			$itemModel = $this->db->selectCollection('Job');
    			$job = $itemModel->findOne(array('_id' => $_REQUEST['id']));
    			if ($job['uploadedFile']) {
    				$job['uploadedFile'][] = $status;
    			} else {
    				$job['uploadedFile'] = [$status];
    			}
    			$itemModel->update(array("_id" => $job['_id']), $job);
    
    			$data = json_encode($status);
    		}
    		// Else if it is a check for progress GET request, return 'no progress'
    	} else if(isset($_GET['check'])) {
    		$status['noprogress'] = true;
    		$data = json_encode($status);
    	}
    	return $this->output->set_output($data);
    }
}