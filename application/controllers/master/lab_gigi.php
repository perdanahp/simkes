<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class lab_gigi extends CI_Controller {

	function __construct() 
	{
        parent::__construct();
		$this->load->helper('url'); // Load Helper URL CI
		$this->load->model('m_lab_gigi'); // Load Model m_jqgrid
                $this->load->library(array('excel','validasi'));
    }
	function index() 
	{
		$this->grid(); //Default di arahkan ke function grid
	}
	function grid() 
	{
		$this->load->view('master/lab_gigi'); // Load View jqgrid
	}
	function json() 
	{
		$page  = $this->input->get('page');
		$limit = $this->input->get('rows');
		$sidx  = $this->input->get('sidx');
		$sord  = $this->input->get('sord');
		
		if(!$sidx) $sidx=1;
		
	
		# Untuk Single Searchingnya #		
		$where = ""; //if there is no search request sent by jqgrid, $where should be empty
		$searchField = isset($_GET['searchField']) ? $_GET['searchField'] : false;
		$searchString = isset($_GET['searchString']) ? $_GET['searchString'] : false;
		if ($_GET['_search'] == 'true') 
		{
			$where = array($searchField => $searchString);
		}
		# End #
		
	        		
		$count = $this->m_lab_gigi->count_lab_gigi($where);
		
		$count > 0 ? $total_pages = ceil($count/$limit) : $total_pages = 0;
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit;
		if($start <0) $start = 0;
		
		$data1 = $this->m_lab_gigi->get_lab_gigi($where, $sidx, $sord, $limit, $start)->result();
	
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$i=0;
		foreach($data1 as $line)
		{
			$responce->rows[$i]['id_provider']   = $line->id_provider;
			
			$responce->rows[$i]['cell'] = array($line->id_provider,$line->nama_provider,$line->almt_provider,$line->langg_provider,$line->email_provider,$line->tlp_provider,$line->fax_provider);
			$i++;
		}
		echo json_encode($responce);
	}
	function crud() 
	{
		$oper=$this->input->post('oper');
		$id_provider=$this->input->post('id');
		$lab_gigi_nama=$this->input->post('nama_provider');
		$lab_gigi_alamat=$this->input->post('almt_provider');
		$lab_gigi_langg=$this->input->post('langg_provider');
		$lab_gigi_email=$this->input->post('email_provider');
		$lab_gigi_fax=$this->input->post('fax_provider');
		$lab_gigi_telp=$this->input->post('tlp_provider');
	    
		
		
		
	    switch ($oper) 
		{
	        case 'add':
				$datanya=array('nama_provider'=>$lab_gigi_nama,'almt_provider'=>$lab_gigi_alamat,'langg_provider'=>$lab_gigi_langg,'email_provider'=>$lab_gigi_email,'fax_provider'=>$lab_gigi_fax,'tlp_provider'=>$lab_gigi_telp,'idjenis_provider'=>2);
				$this->m_lab_gigi->insert_lab_gigi($datanya);
				break;
	        case 'edit':
	            $datanya=array('nama_provider'=>$lab_gigi_nama,'almt_provider'=>$lab_gigi_alamat,'langg_provider'=>$lab_gigi_langg,'email_provider'=>$lab_gigi_email,'fax_provider'=>$lab_gigi_fax,'tlp_provider'=>$lab_gigi_telp,'idjenis_provider'=>2);
				$this->m_lab_gigi->update_lab_gigi($id_provider, $datanya);
	            break;
	        case 'del':
	            $this->m_lab_gigi->delete_lab_gigi($id_provider);
	        break;
		}
	}

        function impor()
	{

        $data['data']='lab_gigi';
	$this->load->view('impor/impor',$data);
	}

        function do_impor()
	{
	$error = "";
	$pesan="";
	$fileElementName = 'uploadlab_gigi';

	if(!empty($_FILES[$fileElementName]['error']))
	{
		switch($_FILES[$fileElementName]['error'])
		{

			case '1':
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case '2':
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case '3':
				$error = 'The uploaded file was only partially uploaded';
				break;
			case '4':
				$error = 'No file was uploaded.';
				break;

			case '6':
				$error = 'Missing a temporary folder';
				break;
			case '7':
				$error = 'Failed to write file to disk';
				break;
			case '8':
				$error = 'File upload stopped by extension';
				break;
			case '999':
			default:
				$error = 'No error code avaiable';
		}
	}
	elseif(empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none')
	{
		$error = 'No file was uploaded..';
	}
	else
	{
			$pesan .= " File Name: " . $_FILES[$fileElementName]['name'] . ", ";
			$pesan .= " File Size: " . @filesize($_FILES[$fileElementName]['tmp_name']);

			//for security reason, we force to remove all uploaded file
			@unlink($_FILES[$fileElementName]);

			$config['upload_path'] = './temp_upload/';
			$config['allowed_types'] = 'xls';

			$this->load->library('upload', $config);
			//$tes=$this->upload->do_upload();


			if ( ! $this->upload->do_upload($fileElementName))
			{
                           $data = array('error' => $this->upload->display_errors());
                           $error="Insert failed. Please check your file, only .xls file allowed.";

                        }

			else
			{
                            $upload_data = $this->upload->data();
                            $file =  $upload_data['full_path'];
                            error_reporting(E_ALL ^ E_NOTICE);

                            $inputFileName = $file;
                            $objReader = new PHPExcel_Reader_Excel5();
                            $objPHPExcel = $objReader->load($file);
                            $data = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
                            $dataexcel = Array();
                            for ($i = 2; $i <= count($data); $i++)
                            {
                            if($data[$i]['A'] == '') break;
                            if(!$this->validasi->cekLengthString($data[$i]['A'],50))
                            {
                                $error="Data Nama Provider di baris ke".$i." terlalu Banyak";
                            }
                            else if(!$this->validasi->cekLengthString($data[$i]['B'],100))
                            {

                                $error="Data Alamat Provider di baris ke".$i." terlalu Banyak";
                            }
                            else if(!$this->validasi->cekLengthString($data[$i]['C'],1))
                            {

                                $error="Data Langganan di baris ke".$i." Harus y atau t";
                            }
                            else if(!$this->validasi->cekLengthString($data[$i]['D'],50))
                            {

                                $error="Data Email Provider di baris ke".$i." terlalu Banyak";
                            }
                            else if(!$this->validasi->cekLengthString($data[$i]['E'],20))
                            {

                                $error="Data Fax Provider di baris ke".$i." terlalu Banyak";
                            }
                            else if(!$this->validasi->cekLengthString($data[$i]['F'],20))
                            {

                                $error="Data Telp Provider di baris ke".$i." terlalu Banyak";
                            }
                            else{
                            $dataexcel[$i-1]['nama_provider'] = $data[$i]['A'];
                            $dataexcel[$i-1]['almt_provider'] = $data[$i]['B'];
                            $dataexcel[$i-1]['langg_provider'] = $data[$i]['C'];
                            $dataexcel[$i-1]['email_provider'] = $data[$i]['D'];
                            $dataexcel[$i-1]['fax_provider'] = $data[$i]['E'];
                            $dataexcel[$i-1]['tlp_provider'] = $data[$i]['F'];
                            $dataexcel[$i-1]['idjenis_provider'] = "2";
                            }
                            }
                            $check=$data[2]['A'];
                            $data['check']=$check;
                            if ($check == "")
                            {
                            $error="Data Tidak Ada";
                            }
                            else if($error == '')
                            {

                            //$pesan.=count($dataexcel);
                            $this->db->trans_start();
                            for($i=1;$i<=count($dataexcel);$i++)
                            {
                             try
                             {
                                     $this->m_lab_gigi->insert_lab_gigi($dataexcel[$i]);

                             }
                             catch(Exception $e)
                             {
                                 $error=$e->getMessage();
                             }
                            }
                            $this->db->trans_complete();
                            //$pesan=$this->db->trans_status();
                            }
			}
                        

	}
	$data['error']=$error;
        $data['pesan']=$pesan;
        echo json_encode($data);
	}

        function ekspor()
	{



	error_reporting(E_ALL);

	date_default_timezone_set('Europe/London');

	// Add some data
                $limit = null;
		$sidx  = "id_provider";
		$sord  = "desc";
                $start = null;
                $where = null;

        $data1 = $this->m_lab_gigi->get_lab_gigi($where, $sidx, $sord, $limit, $start)->result();
	$tulis=$this->excel->setActiveSheetIndex(0);

                        $tulis->setCellValue('A1', "No");
                        $tulis->setCellValue('B1', "Id Provider");
                        $tulis->setCellValue('C1', "Nama Provider");
                        $tulis->setCellValue('D1', "Langganan");
                        $tulis->setCellValue('E1', "Alamat");
                        $tulis->setCellValue('F1', "Email");
                        $tulis->setCellValue('G1', "Telepon");
                        $tulis->setCellValue('H1', "Fax");


        $i=2;
        foreach($data1 as $line)
		{
						$tulis->setCellValue('A'.$i, $i-1);
                        $tulis->setCellValue('B'.$i, $line->id_provider);
                        $tulis->setCellValue('C'.$i, $line->nama_provider);
                        $tulis->setCellValue('D'.$i, $line->langg_provider);
                        $tulis->setCellValue('E'.$i, $line->almt_provider);
                        $tulis->setCellValue('F'.$i, $line->email_provider);
                        $tulis->setCellValue('G'.$i, $line->tlp_provider);
                        $tulis->setCellValue('H'.$i, $line->fax_provider);

			$i++;
		}


      	// Rename worksheet
	$this->excel->getActiveSheet()->setTitle('Lab Gigi');


	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$this->excel->setActiveSheetIndex(0);


	// Redirect output to a client�s web browser (Excel5)
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="Master Lab Gigi.xls"');
	header('Cache-Control: max-age=0');

	$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
	$objWriter->save('php://output');
	//exit;
	}

}