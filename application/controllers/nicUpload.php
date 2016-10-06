<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . '/libraries/UUID.php';

/*
 * NicEdit nicUpload script for Codeigniter
 * Upload script for NicEdit written with Codeigniter's native upload class
 * Saves images using native CI uploader and returns the URL to nicEdit
 * @author: Ben Speakman <ben@cyber-duck.co.uk>
 *
 * https://github.com/danishkhan/NicEdit
 *
 */
class nicUpload extends CI_Controller {

    function __construct()
    {
        parent::__construct();

        // Set upload config vars
        $config['upload_path']   = './uploads/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|pdf';
        $config['overwrite'] = TRUE;
        $config['max_size']      = '20480000';// Can be set to particular file size , here it is 20 MB(20480 Kb)
        $config['max_width']     = '1024';
        $config['max_height']    = '768';

        $this->nicupload_uri = '/uploads';

        $this->load->library('upload', $config);

        $this->output->enable_profiler(FALSE);
        
    } 

    public function do_upload()
    {

        // If it is a upload POST request
        if($this->input->server('REQUEST_METHOD') == 'POST') {

            // Attempt upload
            if ( ! $this->upload->do_upload('file')) {

                // Return any errors
                $data = $this->nic_output(array('error' => $this->upload->display_errors()));

            } else {

                // Upload success
                $upload = array('upload_data' => $this->upload->data());

                $status['done']  = 1;
                $status['width'] = $upload['upload_data']['image_width'];
                $status['url']   = $this->nicupload_uri.'/'.$upload['upload_data']['file_name'];

                // Return upload info to nicEdit
                //$data = $this->nic_output($status);
				$data = json_encode($status);
            }

        // Else if it is a check for progress GET request, return 'no progress'
        } else if(isset($_GET['check'])) {

            $status['noprogress'] = true;
            //$data = $this->nic_output($status);
			$data = json_encode($status);
        }

        return $this->output->set_output($data);

    }
    
    public function do_uploadManual()
    {
    
    	// If it is a upload POST request
    	if($this->input->server('REQUEST_METHOD') == 'POST') {
    
    		// Attempt upload
    		if ( ! $this->upload->do_upload('file')) {
    
    			// Return any errors
    			$data = $this->nic_output(array('error' => $this->upload->display_errors()));
    
    		} else {
    
    			// Upload success
    			$upload = array('upload_data' => $this->upload->data());
    			
    			$status['done']  = 1;
    			$status['width'] = $upload['upload_data']['image_width'];
    			$status['url']   = $this->nicupload_uri.'/'.$upload['upload_data']['file_name'];
    			
    			$con = new MongoClient ();
    			$this->db = $con->selectDB(_DB_NAME);
    			$itemModel = $this->db->selectCollection('Manual');
    			$manual['_id'] = UUID::v4();
    			$manual['name'] = $upload['upload_data']['file_name'];
    			$manual['size'] = $upload['upload_data']['file_size'] . " KB";
    			$manual['uploadDate'] = time();
    			$manual['url'] = $this->nicupload_uri.'/'.$upload['upload_data']['file_name'];
    			$itemModel->save($manual);
    			
    			// Return upload info to nicEdit
    			//$data = $this->nic_output($status);
    			$data = json_encode($status);
    		}
    
    		// Else if it is a check for progress GET request, return 'no progress'
    	} else if(isset($_GET['check'])) {
    
    		$status['noprogress'] = true;
    		//$data = $this->nic_output($status);
    		$data = json_encode($status);
    	}
    
    	return $this->output->set_output($data);
    
    }

    // Send message back to nicUpload
    private function nic_output($status) {

        $script = 'try {'.(($this->input->server('REQUEST_METHOD') == 'POST') ? 'top.' : '').'nicUploadButton.statusCb('.json_encode($status).');} catch(e) { alert(e.message); }';
        
        if($this->input->server('REQUEST_METHOD') == 'POST') {

            return '<script>'.$script.'</script>';

        } else {

            return $script;

        }

    }

    public function example() {

        $this->load->view('nicEditExample');
    
    }
    
}