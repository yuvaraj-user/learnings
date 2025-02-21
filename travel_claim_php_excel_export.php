<?php
    include '../../auto_load.php';
    include '../api/api_helper.php';
    require_once "../../Libraries/Excel/PHPExcel.php";
    error_reporting(1);

    $cdata = $_POST;

    function get_detailed_data($conn,$tr_request_nos,$status,$day_type)
    {
        $detailed_sql = "SELECT *,tp_claims_expenses.createdAt as claim_submission_date,'".$status."' as manual_status,'".$day_type."' as manual_day_type,tp_claims_expenses.l1_action_datetime as claim_l1_action_datetime,tp_claims_expenses.l2_action_datetime as claim_l2_action_datetime,tp_claims_expenses.hod_action_datetime as claim_hod_action_datetime,tp_utr_details.date_of_posting as sap_posting_date,tp_utr_details.date_of_payment_entry as payment_entry_date,tp_utr_details.createdAt as utr_uploaded_date FROM tp_travel_request 
        LEFT JOIN HR_Master_Table on tp_travel_request.emp_code = HR_Master_Table.Employee_Code
        LEFT JOIN tp_claims_expenses on  tp_claims_expenses.tr_req_no = tp_travel_request.request_no
        LEFT JOIN tp_utr_details on tp_utr_details.utr_no = tp_claims_expenses.utr_no AND 
        tp_utr_details.claim_no = tp_claims_expenses.claim_no
        WHERE tp_travel_request.request_no IN(".$tr_request_nos.")";


        $detailed_sql_exec = sqlsrv_query($conn,$detailed_sql);   
        $result = array();
        while ($row = sqlsrv_fetch_array($detailed_sql_exec,SQLSRV_FETCH_ASSOC)) {
            $result[] = $row; 
        }

        return $result;

    }

    function get_courier_postage_data($conn,$tr_request_no,$courier_id)
    {
        $courier_postage_sql = "SELECT createdAt from tp_other_expense_courier_and_postage where id = '".$courier_id."' AND tr_request_no = '".$tr_request_no."'";

        $courier_postage_sql_exec = sqlsrv_query($conn,$courier_postage_sql, array(), array("Scrollable" => 'static'));   

        $result = array();
        while ($row = sqlsrv_fetch_array($courier_postage_sql_exec,SQLSRV_FETCH_ASSOC)) {
            $result[] = $row; 
        }

        return $result;
    }

    $curl_post_arr = array_merge($cdata,array('action' => 'get_travel_claim_pending_report'));


    /* -------------------------------travel claim pending report sheet functionality start ----------------------*/  
    
    /* curl call for consolidated report data get start */
    $response_arr = array();

    // Initialize cURL session
    $ch = curl_init($data);

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, 'https://corporate.rasiseeds.com/corporate/rasiTravelportal/api/travel_detail_filters.php');  // URL to send the data
    curl_setopt($ch, CURLOPT_POST, true);  // Set request type to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($curl_post_arr));  // Convert array to query string
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Set options to handle response (optional)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL request and capture response
    $response = curl_exec($ch);

    // Check for errors
    if(curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
    } else {
        // echo 'Response from file2.php: ' . $response;
        $response_arr = json_decode($response,true);
    }

    /* curl call for consolidated report data get end */
   

    $objPHPExcel    =   new PHPExcel();


    $centerAlignment = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );

    $verticalcenterAlignment = array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            )
    );

    $bgcolor1 = array(
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => '81c1ea')
            )
            
        );
    $bgcolor2 = array(
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => '01AF41')
            )
        );

    $bgcolor3 = array(
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'F5FC28')
            )
        );   


    $grandtotal_bg = array(
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'df6d2d')
        )
    );     

    

    $objPHPExcel->setActiveSheetIndex(0);
    $sheet1 = $objPHPExcel->getActiveSheet(0);
    $sheet1->setTitle("Travel Claim Pending Report");
    $sheet1->setCellValue('A1','Division');
    $sheet1->setCellValue('B1','Status');
    $sheet1->setCellValue('C1','Below 15 Days');
    $sheet1->setCellValue('D1','15 Days Between 30 Days');

    $sheet1->setCellValue('E1','30 Days Between 60 Days');
    $sheet1->setCellValue('F1','60 Days Between 90 Days');
    $sheet1->setCellValue('G1','Above 90 Days');
    $sheet1->setCellValue('H1','Total');
    $sheet1->getStyle("A1:H1")->applyFromArray($centerAlignment);
    $sheet1->getStyle("A1:H1")->getFont()->setBold(true);

    foreach (range('A', 'H') as $key => $range_cell) {
       $objPHPExcel->getActiveSheet()->getColumnDimension($range_cell)->setAutoSize(true);
    }

    $i = 2;
    $below_15day_total = $from_15_to_30days = $from_30_to_60days = $from_60_to_90days = $above_90days = $row_total_grand = 0;
    foreach ($response_arr['data'] as $key => $division_arr) {
        $sheet1->setCellValue('A'.$i , $key);
        $start_index = 'A'.$i;

        $row_total =  0;
        foreach ($division_arr as $dkey => $dvalue) {
            $row_total = $dvalue['below_15day'] + $dvalue['from_15_to_30days'] + $dvalue['from_30_to_60days'] + $dvalue['from_60_to_90days'] +  $dvalue['above_90days'];
            
            if($dkey != 'tr_division_total') {

                $below_15day_total = $below_15day_total + $dvalue['below_15day'];
                $from_15_to_30days = $from_15_to_30days + $dvalue['from_15_to_30days'];
                $from_30_to_60days = $from_30_to_60days + $dvalue['from_30_to_60days'];
                $from_60_to_90days = $from_60_to_90days + $dvalue['from_60_to_90days'];
                $above_90days      = $above_90days + $dvalue['above_90days'];
                $row_total_grand   = $row_total_grand + $row_total;
            }

            $sheet1->setCellValue('B'.$i , $dvalue['status']);
            $sheet1->setCellValue('C'.$i , $dvalue['below_15day']);
            $sheet1->setCellValue('D'.$i , $dvalue['from_15_to_30days']);
            $sheet1->setCellValue('E'.$i , $dvalue['from_30_to_60days']);
            $sheet1->setCellValue('F'.$i , $dvalue['from_60_to_90days']);
            $sheet1->setCellValue('G'.$i , $dvalue['above_90days']);
            $sheet1->setCellValue('H'.$i , $row_total);

            $ending_index = 'A'.($i-1); 
            
            if($dkey == 'tr_division_total') {
                $sheet1->setCellValue('A'.$i , $dvalue['division']);
                $sheet1->getStyle("A".$i.":H".$i)->getFont()->setBold(true);
            }

          
            $i++;   
                
        }

        // start_index and ending_index using merge the division cell vertically 
        $sheet1->mergeCells($start_index.':'.$ending_index);
        $sheet1->getStyle($start_index.':'.$ending_index)->applyFromArray($verticalcenterAlignment);

    }

    // grand total print functionality
    $sheet1->setCellValue('A'.$i , 'Grand Total');
    $sheet1->setCellValue('B'.$i , '');
    $sheet1->setCellValue('C'.$i , $below_15day_total);
    $sheet1->setCellValue('D'.$i , $from_15_to_30days);
    $sheet1->setCellValue('E'.$i , $from_30_to_60days);
    $sheet1->setCellValue('F'.$i , $from_60_to_90days);
    $sheet1->setCellValue('G'.$i , $above_90days);
    $sheet1->setCellValue('H'.$i , $row_total_grand);
    $sheet1->getStyle("A".$i.":H".$i)->getFont()->setBold(true);
    $sheet1->getStyle("A".$i.":H".$i)->applyFromArray($verticalcenterAlignment);
    $sheet1->getStyle("A".$i.":H".$i)->applyFromArray($grandtotal_bg);
    $sheet1->getStyle("A".$i.":H".$i)->getFont()->getColor()->setRGB('FFFFFF');
    // grand total print functionality end 


    /* -------------------------------travel claim pending report sheet functionality end ----------------------*/  

    
    /* -------------------travel claim pending detailed report sheet functionality start  ----------------------*/ 

        $sql ="SELECT tp_business_division.business_division,tp_travel_request.request_no,tp_travel_request.request_date,
        tp_travel_request.tr_approval_status, tp_claims_expenses.tr_req_no,tp_claims_expenses.status,tp_claims_expenses.createdAt,
        tp_travel_request.date_of_receipt,tp_claim_request_approval_history.LatestCreatedAt,tp_claims_expenses.finance_verification_status,tp_claim_hard_copies_submission.createdAt,tp_claims_expenses.utr_no,tp_claims_expenses.finance_verification_dt,

        (CASE WHEN tp_travel_request.tr_approval_status = 'P' AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_approval_pending,
        (CASE WHEN tp_claims_expenses.tr_req_no IS NULL AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_claim_pending,
        (CASE WHEN tp_claims_expenses.status = 'P' AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) <= 15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_claim_approval_pending,
        (CASE WHEN tp_travel_request.date_of_receipt IS NULL AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) <= 15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_claim_hardcopy_pending,
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <=15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_claim_audit_verify_pending,      
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <=15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_claim_sap_posting_pending,      
        (CASE WHEN tp_claims_expenses.utr_no IS NULL AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) <=15 THEN tp_travel_request.request_no ELSE '' END) as below_15day_travel_claim_utr_pending,

        (CASE WHEN tp_travel_request.tr_approval_status = 'P' AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 15 AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_approval_pending',
        (CASE WHEN tp_claims_expenses.tr_req_no IS NULL AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 15 AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_claim_pending',
        (CASE WHEN tp_claims_expenses.status = 'P' AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) > 15 AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_claim_approval_pending',
        (CASE WHEN tp_travel_request.date_of_receipt IS NULL AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) > 15 AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_claim_hardcopy_pending',
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 15 AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_claim_audit_verify_pending',      
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 15 AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_claim_sap_posting_pending',      
        (CASE WHEN tp_claims_expenses.utr_no IS NULL AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) > 15 AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) <= 30 THEN tp_travel_request.request_no ELSE '' END) as '15_to_30days_travel_claim_utr_pending',


        (CASE WHEN tp_travel_request.tr_approval_status = 'P' AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 30 AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_approval_pending',
        (CASE WHEN tp_claims_expenses.tr_req_no IS NULL AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 30 AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_claim_pending',
        (CASE WHEN tp_claims_expenses.status = 'P' AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) > 30 AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_claim_approval_pending',
        (CASE WHEN tp_travel_request.date_of_receipt IS NULL AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) > 30 AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_claim_hardcopy_pending',
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 30 AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_claim_audit_verify_pending',      
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 30 AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_claim_sap_posting_pending',      
        (CASE WHEN tp_claims_expenses.utr_no IS NULL AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) > 30 AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) <= 60 THEN tp_travel_request.request_no ELSE '' END) as '30_to_60days_travel_claim_utr_pending',


        (CASE WHEN tp_travel_request.tr_approval_status = 'P' AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 60 AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_approval_pending',
        (CASE WHEN tp_claims_expenses.tr_req_no IS NULL AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 60 AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_claim_pending',
        (CASE WHEN tp_claims_expenses.status = 'P' AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) > 60 AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_claim_approval_pending',
        (CASE WHEN tp_travel_request.date_of_receipt IS NULL AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) > 60 AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_claim_hardcopy_pending',
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 60 AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_claim_audit_verify_pending',      
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 60 AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_claim_sap_posting_pending',      
        (CASE WHEN tp_claims_expenses.utr_no IS NULL AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) > 60 AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) <= 90 THEN tp_travel_request.request_no ELSE '' END) as '60_to_90days_travel_claim_utr_pending',

        (CASE WHEN tp_travel_request.tr_approval_status = 'P' AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_approval_pending',
        (CASE WHEN tp_claims_expenses.tr_req_no IS NULL AND DATEDIFF(DAY,tp_travel_request.request_date,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_claim_pending',
        (CASE WHEN tp_claims_expenses.status = 'P' AND DATEDIFF(DAY,tp_claims_expenses.createdAt,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_claim_approval_pending',
        (CASE WHEN tp_travel_request.date_of_receipt IS NULL AND DATEDIFF(DAY,tp_claim_request_approval_history.LatestCreatedAt,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_claim_hardcopy_pending',
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_claim_audit_verify_pending',      
        (CASE WHEN tp_claims_expenses.finance_verification_status = 'P' AND DATEDIFF(DAY,tp_claim_hard_copies_submission.createdAt,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_claim_sap_posting_pending',      
        (CASE WHEN tp_claims_expenses.utr_no IS NULL AND DATEDIFF(DAY,tp_claims_expenses.finance_verification_dt,GETDATE()) > 90 THEN tp_travel_request.request_no ELSE '' END) as 'above_90days_travel_claim_utr_pending'


        from tp_travel_request 
        INNER JOIN HR_Master_Table ON HR_Master_Table.Employee_Code = tp_travel_request.emp_code AND HR_Master_Table.Employment_Status = 'Active' 
        INNER JOIN tp_business_division ON tp_business_division.business_division = HR_Master_Table.Business_Division AND tp_business_division.status = '1' 
        LEFT JOIN tp_claims_expenses ON tp_claims_expenses.tr_req_no = tp_travel_request.request_no 
        LEFT JOIN (SELECT claim_no,MAX(CreatedAt) AS LatestCreatedAt FROM tp_claim_request_approval_history
        WHERE status = 'A' group by claim_no) as tp_claim_request_approval_history ON tp_claims_expenses.claim_no = tp_claim_request_approval_history.claim_no
        LEFT JOIN tp_claim_hard_copies_submission ON tp_claim_hard_copies_submission.claim_no = tp_claims_expenses.claim_no WHERE 1=1";


        // top filters functionality
        if(isset($cdata['bdiv']) && (COUNT($cdata['bdiv']) > 0)) {
            $bdiv = "'".implode("','",$cdata['bdiv'])."'";
            $sql .= " AND HR_Master_Table.business_division IN (".$bdiv.")"; 
        }

        if(isset($cdata['department']) && (COUNT($cdata['department']) > 0)) {
            $department = "'".implode("','",$cdata['department'])."'";
            $sql .= " AND HR_Master_Table.Department IN (".$department.")"; 
        }

        if(isset($cdata['zone']) && (COUNT($cdata['zone']) > 0)) {
            $zone = "'".implode("','",$cdata['zone'])."'";
            $sql .= " AND HR_Master_Table.zone IN (".$zone.")"; 
        }   

        if(isset($cdata['region']) && (COUNT($cdata['region']) > 0)) {
            $region = "'".implode("','",$cdata['region'])."'";
            $sql .= " AND HR_Master_Table.state IN (".$region.")"; 
        }  

        if(isset($cdata['territory']) && (COUNT($cdata['territory']) > 0)) {
            $territory = "'".implode("','",$cdata['territory'])."'";
            $sql .= " AND HR_Master_Table.territory IN (".$territory.")"; 
        }                        

        if(isset($cdata['employee']) && (COUNT($cdata['employee']) > 0)) {
            $employee = "'".implode("','",$cdata['employee'])."'";
            $sql .= " AND HR_Master_Table.Employee_Code IN (".$employee.")"; 
        }      

        if((isset($cdata['byear_from_date']) && $cdata['byear_from_date'] != '') && (isset($cdata['byear_to_date']) && $cdata['byear_to_date'] != '')) {

            $sql .= " AND tp_travel_request.from_date >= '".$cdata['byear_from_date']."' AND tp_travel_request.to_date <= '".$cdata['byear_to_date']."'"; 
        } 

        $sql .= " GROUP BY tp_business_division.business_division,tp_travel_request.request_no,tp_travel_request.request_date,tp_travel_request.tr_approval_status, tp_claims_expenses.tr_req_no,tp_claims_expenses.status,tp_claims_expenses.createdAt,
        tp_travel_request.date_of_receipt,tp_claim_request_approval_history.LatestCreatedAt,tp_claims_expenses.finance_verification_status,tp_claim_hard_copies_submission.createdAt,tp_claims_expenses.utr_no,tp_claims_expenses.finance_verification_dt";

        // echo $sql;exit;

        $q = sqlsrv_query($conn, $sql, array(), array("Scrollable" => 'static'));
        $ftrcount = sqlsrv_num_rows($q);        
        
        $response = array();

        if ($ftrcount > 0) {
            while($row = sqlsrv_fetch_array($q,SQLSRV_FETCH_ASSOC)) {

                // travel_approval_pending request number arrays
                if($row['below_15day_travel_approval_pending'] != '') {
                    $response['below_15day_travel_approval_pending'][] = $row['below_15day_travel_approval_pending']; 
                }

                if($row['15_to_30days_travel_approval_pending'] != '') {
                    $response['15_to_30days_travel_approval_pending'][] = $row['15_to_30days_travel_approval_pending']; 
                }

                if($row['30_to_60days_travel_approval_pending'] != '') {
                    $response['30_to_60days_travel_approval_pending'][] = $row['30_to_60days_travel_approval_pending']; 
                }

                if($row['60_to_90days_travel_approval_pending'] != '') {
                    $response['60_to_90days_travel_approval_pending'][] = $row['60_to_90days_travel_approval_pending'];
                } 

                if($row['above_90days_travel_approval_pending'] != '') {
                    $response['above_90days_travel_approval_pending'][] = $row['above_90days_travel_approval_pending']; 
                }

                // travel_claim_pending request number arrays
                if($row['below_15day_travel_claim_pending'] != '') {
                    $response['below_15day_travel_claim_pending'][] = $row['below_15day_travel_claim_pending']; 
                }

                if($row['15_to_30days_travel_claim_pending'] != '') {
                    $response['15_to_30days_travel_claim_pending'][] = $row['15_to_30days_travel_claim_pending']; 
                }

                if($row['30_to_60days_travel_claim_pending'] != '') {
                    $response['30_to_60days_travel_claim_pending'][] = $row['30_to_60days_travel_claim_pending']; 
                }

                if($row['60_to_90days_travel_claim_pending'] != '') {
                    $response['60_to_90days_travel_claim_pending'][] = $row['60_to_90days_travel_claim_pending'];
                } 

                if($row['above_90days_travel_claim_pending'] != '') {
                    $response['above_90days_travel_claim_pending'][] = $row['above_90days_travel_claim_pending']; 
                }


                // travel_claim_approval_pending request number arrays
                if($row['below_15day_travel_claim_approval_pending'] != '') {
                    $response['below_15day_travel_claim_approval_pending'][] = $row['below_15day_travel_claim_approval_pending']; 
                }

                if($row['15_to_30days_travel_claim_approval_pending'] != '') {
                    $response['15_to_30days_travel_claim_approval_pending'][] = $row['15_to_30days_travel_claim_approval_pending']; 
                }

                if($row['30_to_60days_travel_claim_approval_pending'] != '') {
                    $response['30_to_60days_travel_claim_approval_pending'][] = $row['30_to_60days_travel_claim_approval_pending']; 
                }

                if($row['60_to_90days_travel_claim_approval_pending'] != '') {
                    $response['60_to_90days_travel_claim_approval_pending'][] = $row['60_to_90days_travel_claim_approval_pending'];
                } 

                if($row['above_90days_travel_claim_approval_pending'] != '') {
                    $response['above_90days_travel_claim_approval_pending'][] = $row['above_90days_travel_claim_approval_pending']; 
                }

                // travel_claim_hardcopy_pending request number arrays
                if($row['below_15day_travel_claim_hardcopy_pending'] != '') {
                    $response['below_15day_travel_claim_hardcopy_pending'][] = $row['below_15day_travel_claim_hardcopy_pending']; 
                }

                if($row['15_to_30days_travel_claim_hardcopy_pending'] != '') {
                    $response['15_to_30days_travel_claim_hardcopy_pending'][] = $row['15_to_30days_travel_claim_hardcopy_pending']; 
                }

                if($row['30_to_60days_travel_claim_hardcopy_pending'] != '') {
                    $response['30_to_60days_travel_claim_hardcopy_pending'][] = $row['30_to_60days_travel_claim_hardcopy_pending']; 
                }

                if($row['60_to_90days_travel_claim_hardcopy_pending'] != '') {
                    $response['60_to_90days_travel_claim_hardcopy_pending'][] = $row['60_to_90days_travel_claim_hardcopy_pending'];
                } 

                if($row['above_90days_travel_claim_hardcopy_pending'] != '') {
                    $response['above_90days_travel_claim_hardcopy_pending'][] = $row['above_90days_travel_claim_hardcopy_pending']; 
                }    
                

                // travel_claim_audit_verify_pending request number arrays
                if($row['below_15day_travel_claim_audit_verify_pending'] != '') {
                    $response['below_15day_travel_claim_audit_verify_pending'][] = $row['below_15day_travel_claim_audit_verify_pending']; 
                }

                if($row['15_to_30days_travel_claim_audit_verify_pending'] != '') {
                    $response['15_to_30days_travel_claim_audit_verify_pending'][] = $row['15_to_30days_travel_claim_audit_verify_pending']; 
                }

                if($row['30_to_60days_travel_claim_audit_verify_pending'] != '') {
                    $response['30_to_60days_travel_claim_audit_verify_pending'][] = $row['30_to_60days_travel_claim_audit_verify_pending']; 
                }

                if($row['60_to_90days_travel_claim_audit_verify_pending'] != '') {
                    $response['60_to_90days_travel_claim_audit_verify_pending'][] = $row['60_to_90days_travel_claim_audit_verify_pending'];
                } 

                if($row['above_90days_travel_claim_audit_verify_pending'] != '') {
                    $response['above_90days_travel_claim_audit_verify_pending'][] = $row['above_90days_travel_claim_audit_verify_pending']; 
                }

                // travel_claim_sap_posting_pending request number arrays
                if($row['below_15day_travel_claim_sap_posting_pending'] != '') {
                    $response['below_15day_travel_claim_sap_posting_pending'][] = $row['below_15day_travel_claim_sap_posting_pending']; 
                }

                if($row['15_to_30days_travel_claim_sap_posting_pending'] != '') {
                    $response['15_to_30days_travel_claim_sap_posting_pending'][] = $row['15_to_30days_travel_claim_sap_posting_pending']; 
                }

                if($row['30_to_60days_travel_claim_sap_posting_pending'] != '') {
                    $response['30_to_60days_travel_claim_sap_posting_pending'][] = $row['30_to_60days_travel_claim_sap_posting_pending']; 
                }

                if($row['60_to_90days_travel_claim_sap_posting_pending'] != '') {
                    $response['60_to_90days_travel_claim_sap_posting_pending'][] = $row['60_to_90days_travel_claim_sap_posting_pending'];
                } 

                if($row['above_90days_travel_claim_sap_posting_pending'] != '') {
                    $response['above_90days_travel_claim_sap_posting_pending'][] = $row['above_90days_travel_claim_sap_posting_pending']; 
                }  

                // travel_claim_utr_pending request number arrays
                if($row['below_15day_travel_claim_utr_pending'] != '') {
                    $response['below_15day_travel_claim_utr_pending'][] = $row['below_15day_travel_claim_utr_pending']; 
                }

                if($row['15_to_30days_travel_claim_utr_pending'] != '') {
                    $response['15_to_30days_travel_claim_utr_pending'][] = $row['15_to_30days_travel_claim_utr_pending']; 
                }

                if($row['30_to_60days_travel_claim_utr_pending'] != '') {
                    $response['30_to_60days_travel_claim_utr_pending'][] = $row['30_to_60days_travel_claim_utr_pending']; 
                }

                if($row['60_to_90days_travel_claim_utr_pending'] != '') {
                    $response['60_to_90days_travel_claim_utr_pending'][] = $row['60_to_90days_travel_claim_utr_pending'];
                } 

                if($row['above_90days_travel_claim_utr_pending'] != '') {
                    $response['above_90days_travel_claim_utr_pending'][] = $row['above_90days_travel_claim_utr_pending']; 
                }                                


        
            }
        }


        $status_arr = ['TR Approval pending','TR Claim pending','TR Claim approval pending', 'TR Claim Hard copy submission pending','TR Claim audit verification pending','TR Claim SAP entry posting pending','TR Claim UTR update pending'];


        $below_15day_tr_approval_pending_arr = $f15_to_30days_tr_approval_pending_arr = $f30_to_60days_tr_approval_pending_arr = $f60_to_90days_tr_approval_pending_arr = $above_90days_tr_approval_pending_arr = $below_15day_tr_claim_pending_arr = $f15_to_30days_tr_claim_pending_arr = $f30_to_60days_tr_claim_pending_arr = $f60_to_90days_tr_claim_pending_arr = $above_90days_tr_claim_pending_arr = $below_15day_tr_claim_approval_pending_arr = $f15_to_30days_tr_claim_approval_pending_arr = $f30_to_60days_tr_claim_approval_pending_arr = $f60_to_90days_tr_claim_approval_pending_arr = $above_90days_tr_claim_approval_pending_arr = $below_15day_tr_claim_hardcopy_pending_arr = $f15_to_30days_tr_claim_hardcopy_pending_arr = $f30_to_60days_tr_claim_hardcopy_pending_arr = $f60_to_90days_tr_claim_hardcopy_pending_arr = $above_90days_tr_claim_hardcopy_pending_arr = $below_15day_tr_claim_audit_pending_arr = $f15_to_30days_tr_claim_audit_pending_arr = $f30_to_60days_tr_claim_audit_pending_arr = $f60_to_90days_tr_claim_audit_pending_arr = $above_90days_tr_claim_audit_pending_arr = $below_15day_tr_claim_sap_pending_arr = $f15_to_30days_tr_claim_sap_pending_arr = $f30_to_60days_tr_claim_sap_pending_arr = $f60_to_90days_tr_claim_sap_pending_arr = $above_90days_tr_claim_sap_pending_arr = $below_15day_tr_claim_utr_pending_arr = $f15_to_30days_tr_claim_utr_pending_arr = $f30_to_60days_tr_claim_utr_pending_arr = $f60_to_90days_tr_claim_utr_pending_arr = $above_90days_tr_claim_utr_pending_arr = array();

        // status and day type wise travel request details get and merge arrays in single array functionality start
        foreach ($status_arr as $key => $status_value) {
            if($status_value == 'TR Approval pending') {

                if(isset($response['below_15day_travel_approval_pending']) && COUNT($response['below_15day_travel_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_approval_pending'])."'";
                    $below_15day_tr_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                }

                if(isset($response['15_to_30days_travel_approval_pending']) && COUNT($response['15_to_30days_travel_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_approval_pending'])."'";
                    $f15_to_30days_tr_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_approval_pending']) && COUNT($response['30_to_60days_travel_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_approval_pending'])."'";
                    $f30_to_60days_tr_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_approval_pending']) && COUNT($response['60_to_90days_travel_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_approval_pending'])."'";
                    $f60_to_90days_tr_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_approval_pending']) && COUNT($response['above_90days_travel_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_approval_pending'])."'";
                    $above_90days_tr_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                                
                                  
                                                  

            }

            elseif ($status_value == 'TR Claim pending') {
                if(isset($response['below_15day_travel_claim_pending']) && COUNT($response['below_15day_travel_claim_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_claim_pending'])."'";
                    $below_15day_tr_claim_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                } 

                if(isset($response['15_to_30days_travel_claim_pending']) && COUNT($response['15_to_30days_travel_claim_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_claim_pending'])."'";
                    $f15_to_30days_tr_claim_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_claim_pending']) && COUNT($response['30_to_60days_travel_claim_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_claim_pending'])."'";
                    $f30_to_60days_tr_claim_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_claim_pending']) && COUNT($response['60_to_90days_travel_claim_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_claim_pending'])."'";
                    $f60_to_90days_tr_claim_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_claim_pending']) && COUNT($response['above_90days_travel_claim_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_claim_pending'])."'";
                    $above_90days_tr_claim_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                     
            }

            elseif ($status_value == 'TR Claim approval pending') {
                if(isset($response['below_15day_travel_claim_approval_pending']) && COUNT($response['below_15day_travel_claim_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_claim_approval_pending'])."'";
                    $below_15day_tr_claim_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                } 

                if(isset($response['15_to_30days_travel_claim_approval_pending']) && COUNT($response['15_to_30days_travel_claim_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_claim_approval_pending'])."'";
                    $f15_to_30days_tr_claim_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_claim_approval_pending']) && COUNT($response['30_to_60days_travel_claim_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_claim_approval_pending'])."'";
                    $f30_to_60days_tr_claim_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_claim_approval_pending']) && COUNT($response['60_to_90days_travel_claim_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_claim_approval_pending'])."'";
                    $f60_to_90days_tr_claim_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_claim_approval_pending']) && COUNT($response['above_90days_travel_claim_approval_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_claim_approval_pending'])."'";
                    $above_90days_tr_claim_approval_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                
            }

            elseif ($status_value == 'TR Claim Hard copy submission pending') {
               if(isset($response['below_15day_travel_claim_hardcopy_pending']) && COUNT($response['below_15day_travel_claim_hardcopy_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_claim_hardcopy_pending'])."'";
                    $below_15day_tr_claim_hardcopy_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                } 

                if(isset($response['15_to_30days_travel_claim_hardcopy_pending']) && COUNT($response['15_to_30days_travel_claim_hardcopy_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_claim_hardcopy_pending'])."'";
                    $f15_to_30days_tr_claim_hardcopy_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_claim_hardcopy_pending']) && COUNT($response['30_to_60days_travel_claim_hardcopy_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_claim_hardcopy_pending'])."'";
                    $f30_to_60days_tr_claim_hardcopy_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_claim_hardcopy_pending']) && COUNT($response['60_to_90days_travel_claim_hardcopy_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_claim_hardcopy_pending'])."'";
                    $f60_to_90days_tr_claim_hardcopy_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_claim_hardcopy_pending']) && COUNT($response['above_90days_travel_claim_hardcopy_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_claim_hardcopy_pending'])."'";
                    $above_90days_tr_claim_hardcopy_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                 
            }

            elseif ($status_value == 'TR Claim audit verification pending') {
                if(isset($response['below_15day_travel_claim_audit_verify_pending']) && COUNT($response['below_15day_travel_claim_audit_verify_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_claim_audit_verify_pending'])."'";
                    $below_15day_tr_claim_audit_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                } 

                if(isset($response['15_to_30days_travel_claim_audit_verify_pending']) && COUNT($response['15_to_30days_travel_claim_audit_verify_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_claim_audit_verify_pending'])."'";
                    $f15_to_30days_tr_claim_audit_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_claim_audit_verify_pending']) && COUNT($response['30_to_60days_travel_claim_audit_verify_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_claim_audit_verify_pending'])."'";
                    $f30_to_60days_tr_claim_audit_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_claim_audit_verify_pending']) && COUNT($response['60_to_90days_travel_claim_audit_verify_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_claim_audit_verify_pending'])."'";
                    $f60_to_90days_tr_claim_audit_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_claim_audit_verify_pending']) && COUNT($response['above_90days_travel_claim_audit_verify_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_claim_audit_verify_pending'])."'";
                    $above_90days_tr_claim_audit_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                
            }

            elseif ($status_value == 'TR Claim SAP entry posting pending') {
                if(isset($response['below_15day_travel_claim_sap_posting_pending']) && COUNT($response['below_15day_travel_claim_sap_posting_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_claim_sap_posting_pending'])."'";
                    $below_15day_tr_claim_sap_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                }

                if(isset($response['15_to_30days_travel_claim_sap_posting_pending']) && COUNT($response['15_to_30days_travel_claim_sap_posting_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_claim_sap_posting_pending'])."'";
                    $f15_to_30days_tr_claim_sap_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_claim_sap_posting_pending']) && COUNT($response['30_to_60days_travel_claim_sap_posting_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_claim_sap_posting_pending'])."'";
                    $f30_to_60days_tr_claim_sap_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_claim_sap_posting_pending']) && COUNT($response['60_to_90days_travel_claim_sap_posting_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_claim_sap_posting_pending'])."'";
                    $f60_to_90days_tr_claim_sap_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_claim_sap_posting_pending']) && COUNT($response['above_90days_travel_claim_sap_posting_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_claim_sap_posting_pending'])."'";
                    $above_90days_tr_claim_sap_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                   
            }

            elseif ($status_value == 'TR Claim UTR update pending') {
                if(isset($response['below_15day_travel_claim_utr_pending']) && COUNT($response['below_15day_travel_claim_utr_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['below_15day_travel_claim_utr_pending'])."'";
                    $below_15day_tr_claim_utr_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'below_15day');
                } 

                if(isset($response['15_to_30days_travel_claim_utr_pending']) && COUNT($response['15_to_30days_travel_claim_utr_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['15_to_30days_travel_claim_utr_pending'])."'";
                    $f15_to_30days_tr_claim_utr_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'15_to_30days');
                }
               
    
                if(isset($response['30_to_60days_travel_claim_utr_pending']) && COUNT($response['30_to_60days_travel_claim_utr_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['30_to_60days_travel_claim_utr_pending'])."'";
                    $f30_to_60days_tr_claim_utr_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'30_to_60days');
                }

                if(isset($response['60_to_90days_travel_claim_utr_pending']) && COUNT($response['60_to_90days_travel_claim_utr_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['60_to_90days_travel_claim_utr_pending'])."'";
                    $f60_to_90days_tr_claim_utr_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'60_to_90days');
                }

                if(isset($response['above_90days_travel_claim_utr_pending']) && COUNT($response['above_90days_travel_claim_utr_pending']) > 0) {

                    $tr_request_nos = "'".implode("','",$response['above_90days_travel_claim_utr_pending'])."'";
                    $above_90days_tr_claim_utr_pending_arr =  get_detailed_data($conn,$tr_request_nos,$status_value,'above_90days');
                }                
            }                                                            
        }


        $merged_arr = array_merge($below_15day_tr_approval_pending_arr,$f15_to_30days_tr_approval_pending_arr,$f30_to_60days_tr_approval_pending_arr,$f60_to_90days_tr_approval_pending_arr,$above_90days_tr_approval_pending_arr,$below_15day_tr_claim_pending_arr,$f15_to_30days_tr_claim_pending_arr,$f30_to_60days_tr_claim_pending_arr,$f60_to_90days_tr_claim_pending_arr,$above_90days_tr_claim_pending_arr,
            $below_15day_tr_claim_approval_pending_arr,$f15_to_30days_tr_claim_approval_pending_arr,$f30_to_60days_tr_claim_approval_pending_arr,$f60_to_90days_tr_claim_approval_pending_arr,$above_90days_tr_claim_approval_pending_arr,$below_15day_tr_claim_hardcopy_pending_arr,$f15_to_30days_tr_claim_hardcopy_pending_arr,$f30_to_60days_tr_claim_hardcopy_pending_arr,$f60_to_90days_tr_claim_hardcopy_pending_arr,$above_90days_tr_claim_hardcopy_pending_arr,$below_15day_tr_claim_audit_pending_arr,$f15_to_30days_tr_claim_audit_pending_arr,$f30_to_60days_tr_claim_audit_pending_arr,$f60_to_90days_tr_claim_audit_pending_arr,$above_90days_tr_claim_audit_pending_arr,$below_15day_tr_claim_sap_pending_arr,$f15_to_30days_tr_claim_sap_pending_arr,$f30_to_60days_tr_claim_sap_pending_arr,$f60_to_90days_tr_claim_sap_pending_arr,$above_90days_tr_claim_sap_pending_arr,$below_15day_tr_claim_utr_pending_arr,$f15_to_30days_tr_claim_utr_pending_arr,$f30_to_60days_tr_claim_utr_pending_arr,$f60_to_90days_tr_claim_utr_pending_arr,$above_90days_tr_claim_utr_pending_arr);
        // status and day type wise travel request details get and merge arrays in single array functionality end 


    
        $objWorkSheet = $objPHPExcel->createSheet(1);
        $objPHPExcel->setActiveSheetIndex(1);
        $sheet = $objPHPExcel->getActiveSheet(1);
        $sheet->setTitle("Travel Claim Detailed Report");

        $sheet->mergeCells("A1:D1");
        $sheet->mergeCells("E1:L1");
        $sheet->mergeCells("M1:AD1");

        $sheet->setCellValue('A1','Employee Details');
        $sheet->setCellValue('E1','Travel Request (TR)');
        $sheet->setCellValue('M1','Travel Expense Claim');

        $sheet->getStyle("A1:AD1")->applyFromArray($centerAlignment);
        $sheet->getStyle("A1:AD1")->getFont()->setBold(true);

        $sheet->getStyle("A1:D1")->applyFromArray($bgcolor1);
        $sheet->getStyle("E1:L1")->applyFromArray($bgcolor2);
        $sheet->getStyle("M1:AD1")->applyFromArray($bgcolor3);
        $sheet->getStyle('A1:L1')->getFont()->getColor()->setRGB('FFFFFF');


        $sheet->setCellValue('A2','Employee Code');
        $sheet->setCellValue('B2','Employee Name');
        $sheet->setCellValue('C2','Department');
        $sheet->setCellValue('D2','Division');
        $sheet->setCellValue('E2','Travel request creation date');
        $sheet->setCellValue('F2','Travel request no');
        $sheet->setCellValue('G2','From date');
        $sheet->setCellValue('H2','To date');
        $sheet->setCellValue('I2','Date of approval(L1)');
        $sheet->setCellValue('J2','Date of approval(L2)');
        $sheet->setCellValue('K2','Date of approval(HOD)');
        $sheet->setCellValue('L2','Status of TR');
        $sheet->setCellValue('M2','Claim submission date');
        $sheet->setCellValue('N2','Claim no');
        $sheet->setCellValue('O2','Date of courier/post');
        $sheet->setCellValue('P2','POD number');
        $sheet->setCellValue('Q2','Date of receipt');
        $sheet->setCellValue('R2','Account queries/comments');
        $sheet->setCellValue('S2','Date of approval(L1)');
        $sheet->setCellValue('T2','Date of approval(L2)');
        $sheet->setCellValue('U2','Date of approval(HOD)');
        $sheet->setCellValue('V2','Total amount');
        $sheet->setCellValue('W2','Net amount');
        $sheet->setCellValue('X2','Date of audit');
        $sheet->setCellValue('Y2','Audit queries/comments');
        $sheet->setCellValue('Z2','Date of posting in SAP');
        $sheet->setCellValue('AA2','Date of payment entry in SAP');
        $sheet->setCellValue('AB2','Date of upload of UTR in portal');
        $sheet->setCellValue('AC2','Status of claim');
        $sheet->setCellValue('AD2','Day Type');                                 

        $sheet->getStyle("A2:AD2")->applyFromArray($centerAlignment);
        $sheet->getStyle("A2:AD2")->getFont()->setBold(true);

        foreach (range('A', 'Z') as $key => $range_cell) {
           $objPHPExcel->getActiveSheet()->getColumnDimension($range_cell)->setAutoSize(true);
        }

        $extra_columns = ['AA','AB','AC','AD'];

        foreach ($extra_columns as $key => $extra_range_cell) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($extra_range_cell)->setAutoSize(true);
        }

              

        $i = 3;
        foreach ($merged_arr as $key => $value) {

            if($value['tr_approval_status'] == 'A') {
                $tr_status = 'Approved';
            } elseif ($value['tr_approval_status'] == 'P') {
                $tr_status = 'Pending';
            } elseif ($value['tr_approval_status'] == 'R') {
                $tr_status = 'Rejected';
            } elseif ($value['tr_approval_status'] == 'S') {
                $tr_status = 'Saved';
            }

            
            $travel_l1_action_datetime = ($value['l1_action_datetime'] != '') ? $value['l1_action_datetime']->format('d-m-Y H:i:s') : '';
            $travel_l2_action_datetime = ($value['l2_action_datetime'] != '') ? $value['l2_action_datetime']->format('d-m-Y H:i:s') : '';
            $travel_hod_action_datetime = ($value['hod_action_datetime'] != '') ? $value['hod_action_datetime']->format('d-m-Y H:i:s') : '';
            $date_of_receipt = ($value['date_of_receipt'] != '') ? $value['date_of_receipt']->format('d-m-Y H:i:s') : '';

            $claim_sbumission_date = ($value['claim_submission_date'] != '') ? $value['claim_submission_date']->format('d-m-Y H:i:s') : '';
            $claim_l1_action_datetime = ($value['claim_l1_action_datetime'] != '') ? $value['claim_l1_action_datetime']->format('d-m-Y H:i:s') : '';
            $cliam_l2_action_datetime = ($value['claim_l2_action_datetime'] != '') ? $value['claim_l2_action_datetime']->format('d-m-Y H:i:s') : '';
            $cliam_hod_action_datetime = ($value['claim_hod_action_datetime'] != '') ? $value['claim_hod_action_datetime']->format('d-m-Y H:i:s') : '';  
            $date_of_audit = ($value['dateofaudit'] != '') ? $value['dateofaudit']->format('d-m-Y H:i:s') : '';

            $sap_posting_date = ($value['sap_posting_date'] != '') ? $value['sap_posting_date']->format('d-m-Y H:i:s') : '';
            $payment_entry_date = ($value['payment_entry_date'] != '') ? $value['payment_entry_date']->format('d-m-Y H:i:s') : '';
            $utr_uploaded_date = ($value['utr_uploaded_date'] != '') ? $value['utr_uploaded_date']->format('d-m-Y H:i:s') : '';

            // courier date get functionality
            $courier_ids = ($value['courier_and_postage'] != '') ? explode(',',$value['courier_and_postage']) : '';
            $courier_data =array();
            if($courier_ids != '') {
                $last_courier_id = end($courier_ids);
                $courier_data = get_courier_postage_data($conn,$value['request_no'],$last_courier_id);
            }

            $courier_date = (COUNT($courier_data) > 0) ? $courier_data['createdAt']->format('d-m-Y') : '';

            // echo "<pre>";print_r($value);exit;
            $sheet->setCellValue('A'.$i , $value['Employee_Code']);
            $sheet->setCellValue('B'.$i , $value['Employee_Name']);
            $sheet->setCellValue('C'.$i , $value['Department']);
            $sheet->setCellValue('B'.$i , $value['Business_Division']);

            $sheet->setCellValue('E'.$i, $value['request_date']->format('d-m-Y'));
            $sheet->setCellValue('F'.$i, $value['request_no']);
            $sheet->setCellValue('G'.$i, $value['from_date']->format('d-m-Y'));
            $sheet->setCellValue('H'.$i, $value['to_date']->format('d-m-Y'));
            $sheet->setCellValue('I'.$i, $travel_l1_action_datetime);
            $sheet->setCellValue('J'.$i, $travel_l2_action_datetime);
            $sheet->setCellValue('K'.$i, $travel_hod_action_datetime);
            $sheet->setCellValue('L'.$i, $tr_status);
            $sheet->setCellValue('M'.$i, $claim_sbumission_date);
            $sheet->setCellValue('N'.$i, $value['claim_no']);
            $sheet->setCellValue('O'.$i, $courier_date);
            $sheet->setCellValue('P'.$i, $value['podnum']);
            $sheet->setCellValue('Q'.$i, $date_of_receipt);
            $sheet->setCellValue('R'.$i, $value['account_queries_or_comments']);
            $sheet->setCellValue('S'.$i, $claim_l1_action_datetime);
            $sheet->setCellValue('T'.$i, $claim_l2_action_datetime);
            $sheet->setCellValue('U'.$i, $cliam_hod_action_datetime);
            $sheet->setCellValue('V'.$i, $value['total_amount']);
            $sheet->setCellValue('W'.$i, $value['net_amount']);
            $sheet->setCellValue('X'.$i, $date_of_audit);
            $sheet->setCellValue('Y'.$i, $value['audit_queries_or_comments']);
            $sheet->setCellValue('Z'.$i, $sap_posting_date);
            $sheet->setCellValue('AA'.$i, $payment_entry_date);
            $sheet->setCellValue('AB'.$i, $utr_uploaded_date);
            $sheet->setCellValue('AC'.$i, $value['manual_status']);
            $sheet->setCellValue('AD'.$i, $value['manual_day_type']);            

            $i++;

        }
    /* -------------------travel claim pending detailed report sheet functionality start  ----------------------*/ 

    

        $objPHPExcel->setActiveSheetIndex(0);

        $objWriter  =   new PHPExcel_Writer_Excel2007($objPHPExcel);
     
     
        // header('Content-Type: application/vnd.ms-excel'); //mime type
        // header('Content-Disposition: attachment;filename="travel_claim_pending_report.xlsx"'); //tell browser what's the file name
        // header('Cache-Control: max-age=0'); //no cache
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  
        ob_clean();
        ob_start();
        $objWriter->save('php://output');
        $pdfData = ob_get_contents();
        ob_end_clean();

        $excel_file_url = "data:application/pdf; base64,".base64_encode($pdfData);

        echo json_encode($excel_file_url);


?>
