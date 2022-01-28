<?php defined('BASEPATH') OR exit('No direct script access allowed');

class AuditStatusModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('InitDefaultModel');
        $this->load->model('AuditsModel');
    }

    public function setConnection($db2) {
       
        $this->db2 = $db2;
    }

    /**
     * Description : Returns Audit list in the form which datatable expects
     * Note : 
    */
    public function getPolicyListForStatus($postdata){
        $flag = 0;
        $where ='';
        if($this->currentUser['role_id']==8){
                $where .= "  nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
                $flag = 1;            
            if($this->input->get('carrier_old_audits', TRUE) == 1){
                //only for carrier login paid
                $where .= "  AND nr_audit_status.status_id IN(24,45) ";
                // $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(1);//getting Paid audits for carrier
                // !empty($billedAuditsIds)?$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")"):null;
            }
            if($this->input->get('carrier_current_audits', TRUE) == 1){
                $access_status_id = $this->MastersModel->getTableWiseColumnValue('nr_notify_settings', 'status_ids', array('type' => 'carrier_current_audits_status_ids'));
                $where .= "  AND nr_audit_status.status_id IN(".$access_status_id.") ";
                // $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(2);
                // !empty($billedAuditsIds)?$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")"):null;
            }
        }else if($this->currentUser['role_id']==5){
            if(!$this->input->get('testAudit', TRUE)){
                //if the auditor_old_audits flag is set then return policy for auditor only invoice/statment generated
                if($this->input->get('auditor_old_audits', TRUE) == 1){
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise(1);
                }else{
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
                }
                if($audit_ids){
                    $splitLarge =explode(',', $audit_ids);
                    if(sizeof($splitLarge)>50){
                        $newArray = array_chunk($splitLarge,50);
                        $where .= '(';
                        end($newArray);
                        $Lastkey = key($newArray);
                        foreach ($newArray as $key => $value) {
                            if($key == $Lastkey){
                                $where .="nr_audits.audit_id in (". implode(',',$value) .")";
                            }else{
                                $where .="nr_audits.audit_id in (". implode(',',$value) .") OR ";
                            }
                        }
                        $where .= ')';

                    }else{
                        $where .='nr_audits.audit_id IN ('.$audit_ids.') ';
                    }
                }
                if($where == ""){
                    $where .= "nr_audits.status_id NOT IN (46) ";
                }else{
                    $where .= " AND nr_audits.status_id NOT IN (46) ";
                }
                $flag = 1;
                //$this->db2->where("nr_audits.status_id NOT IN (46)",NULL, false);
            }
        }else if($this->currentUser['role_id'] == 6){
            if($flag == 1){
                $where .=" AND nr_audits.pick_by = '".$this->currentUser['user_id']."'"; 
            }else{
                $where .=" nr_audits.pick_by = '".$this->currentUser['user_id']."'";
                $flag = 1;
            }
        }else if($this->currentUser['role_id'] == 1){
            $users = $this->AutocompleteModel->getActiveUserRoleWise(6);
            $user_id = '';
            foreach ($users as $users_key => $users_value) {
                $user_id .= $users_value['id'].',';        
            }            
            $user_ids = substr ( $user_id , 0 , strlen($user_id) -1 );      
            $audit_ids_explode = explode(",", $user_ids);
            if($flag == 1){
                if(isset($postdata['status_id']) AND $postdata['status_id'] == 55){
                    $where .="nr_audits.pick_by in (". implode(',',$audit_ids_explode) .")";
                }
            }else{
                if(isset($postdata['status_id']) AND $postdata['status_id'] == 55){
                    $where .="nr_audits.pick_by in (". implode(',',$audit_ids_explode) .")";
                    $flag = 1;
                }
            }
        }
        if(isset($postdata['status_id'])){
            if($postdata['status_id'] == 1){
                $new_status_ids = $this->new_status_allow_audit_status();
                //$this->db2->where("nr_audits.status_id IN (".$new_status_ids['status_ids'].")");
                $where .= " nr_audits.status_id IN (".$new_status_ids['status_ids'].")";
            }else if($postdata['status_id'] == 24){//carrier Billed
                $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(2);//getting billed audits for carrier
                //$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")");
                $where .= " nr_audits.audit_id IN (".$billedAuditsIds.")";
            }else if($postdata['status_id'] == 25){//carrier Paid
                $paidAuditsIds = $this->getCarrierBilledPaidAuidtsIds(1);//getting paid audits for carrier
                $this->db2->where("nr_audits.audit_id IN (".$paidAuditsIds.")");
            }else{
                //$this->db2->where("nr_audits.status_id IN (".$postdata['status_id'].")");                
                if($postdata['status_id']!=55){
                    if($flag == 1){
                        $where .= " AND nr_audits.status_id IN (".$postdata['status_id'].")";
                    }else{
                        $where .= " nr_audits.status_id IN (".$postdata['status_id'].")";                    
                    }
                }
            }
        }else{
            //getting policy with action if there is status id using querystring variable from url
            if($this->input->get('freeze', TRUE) == 1){
                if($flag == 1){
                    $where .= " AND nr_audits.is_freeze = '1' ";
                }else{
                    $where .= "nr_audits.is_freeze = '1' ";
                }
            }
            if($this->input->get('testAudit', TRUE) == 1){
                if($this->currentUser['role_id']==8){
                    $where .= " AND nr_audits.is_test_audit = '1' ";
                }else{
                    if($flag == 1){
                        $where .= " AND nr_audits.is_test_audit = '1' ";
                    }else{
                        $where .= "nr_audits.is_test_audit = '1' ";
                    }
                }
                //$ids = $this->getInvoiceStatmentGeneratedIds();
                //$this->db2->where("nr_audits.audit_id NOT IN (".$ids.")");
                //$this->db2->where("nr_audits.status_id IN (".$postdata['status_id'].")");
                if($this->currentUser['role_id']==5){
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
                    if($audit_ids){
                        $splitLarge =explode(',', $audit_ids);
                        if(sizeof($splitLarge)>0){
                            $where .= ' AND ';
                        }
                        if(sizeof($splitLarge)>50){
                            $newArray = array_chunk($splitLarge,50);
                            $where .= '(';
                            end($newArray);
                            $Lastkey = key($newArray);
                            foreach ($newArray as $key => $value) {
                                if($key == $Lastkey){
                                    $where .="nr_audits.audit_id in (". implode(',',$value) .")";
                                }else{
                                    $where .="nr_audits.audit_id in (". implode(',',$value) .") OR ";
                                }
                            }
                            $where .= ')';

                        }else{
                            $where .='nr_audits.audit_id IN ('.$audit_ids.') ';
                        }
                    }
                }
            }
        }

        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if($search!=""){
            if($where!=""){
                $where .= " AND ";
            }            
        }
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audit_status.status_text like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR NRU.first_name like '%".$search."%'
                    OR NRU.last_name like '%".$search."%'
                    OR nr_urgent_memo.notes like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }
        $flag = 0;
        if(sizeof($postdata['filter_data'])){
                if($where!=""){
                    $where .= " AND ";
                }
                
                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
               
                if(isset($postdata['filter_data']['county'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_county like '%".$postdata['filter_data']['county']."%'";
                    }else{
                        $where .= "nr_address.r_county like '%".$postdata['filter_data']['county']."%'";
                        $flag=1;
                    }
                }
                
                if(isset($postdata['filter_data']['state'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                    }else{
                        $where .= "nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }                           
                }

                if(isset($postdata['filter_data']['reviewer_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits.pick_by='".$postdata['filter_data']['reviewer_id']."'";
                    }else{
                        $where .= "nr_audits.pick_by='".$postdata['filter_data']['reviewer_id']."'";
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['source'])){
                    if($postdata['filter_data']['source'] == 0){
                       $source="Manual Entry";
                    }else if($postdata['filter_data']['source'] == 1){
                       $source="PDF Extraction";
                    }else if($postdata['filter_data']['source'] == 2){
                       $source="WEB Extraction";
                    }else if($postdata['filter_data']['source'] == 3){
                       $source="XML Extraction";
                    }

                    if($flag==1){
                        $where .=" AND nr_audits.source ='".$source."'";
                    }else{
                        $where .="nr_audits.source ='".$source."'";
                    }
                }
        }
        if($where!=''){
            //$where .= "AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= " AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $this->db2->where($where);
        }else{
            //$where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = '1'";
            $where .= "(nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $this->db2->where($where);
        }
        if($length!=-1){
            $this->db2->limit($length,$start);
        }
        return $this->db2
            ->select("
                    schedule_id, fed_id,
                    nr_audits.audit_id,nr_audits.created_date,nr_audits.source,nr_audits.is_active,nr_audits.audit_group_id,nr_audits.is_primary_in_group,nr_audits.is_non_coop,nr_audits.status_id,
                    nr_audits.modified_date,
                    IFNULL(nr_reminders.reminder_days,0) AS reminder_days,
                    nr_reminders.extra AS reminder_extra,
                    nr_audit_group.group_name AS audit_group_name,
                    APD.policy_number, APD.policy_period_to,
                    APD.created_date as policy_created_date,
                    AID.insured_name,
                    nr_contacts.contact AS phone_number,
                    cm.carrier_name,
                    nr_audit_type.audit_type_text,
                    nr_address.r_lat,nr_address.r_lon,nr_address.r_country,r_county,nr_address.r_city,nr_address.r_zipcode,nr_address.r_state,nr_address.r_address,
                    nr_audit_status.status_text,
                    nr_audit_status.color_code,
                    nr_audits_xml_details.control_id,
                    nr_audit_schedules.schedule_date,
                    CONCAT(nr_users.first_name , ' ' , nr_users.last_name) AS auditor_name,
                    CONCAT(NRU.first_name , ' ' , NRU.last_name) as pick_by_name, 
                    nr_urgent_memo.notes,nr_urgent_memo.memo_id,nr_urgent_memo.created_by AS creator_id,
                    nr_urgent_memo.is_deleted AS urgent_mome_is_delete,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags,
                    CONCAT(nr_states.state_name , '-' , nr_states.state_description) AS state_name,
                    nr_audit_status_request.note as auditRequestNote
                ")
        ->order_by($column_name,$order)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_audit_group','nr_audit_group.group_id=nr_audits.audit_group_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_reminders','nr_reminders.related_id=nr_audits.audit_id AND nr_reminders.related_type = "1" AND nr_reminders.user_id = ' . $user_id,'left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id and nr_audit_status.status_id !=15 and nr_audit_status.status_id !=20','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id = nr_audit_schedules.user_id', 'left')
        ->join('nr_users AS NRU','NRU.user_id = nr_audits.pick_by AND nr_audits.pick_by','left')
        ->join('nr_urgent_memo','nr_audits.audit_id=nr_urgent_memo.audit_id AND nr_urgent_memo.is_deleted=0','left')
        ->join('nr_auditor_invoice_details','nr_audits.audit_id=nr_auditor_invoice_details.audit_id','left')
        ->join('nr_carrier_statement_details','nr_audits.audit_id=nr_carrier_statement_details.audit_id','left')
        ->join('nr_states','(nr_address.r_state = nr_states.state_description OR nr_address.r_state = nr_states.state_name)','left')
        ->join('nr_audit_status_request','nr_audits.audit_id = nr_audit_status_request.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
     * Description : Returns Filtered Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getPolicyListForStatusFilterTot($postdata){
        $flag = 0;
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
            $flag = 1;
            if($this->input->get('carrier_old_audits', TRUE) == 1){
                //only for carrier login paid
                $where .= "  AND nr_audit_status.status_id IN(24,45) ";
                // $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(1);//getting Paid audits for carrier
                // !empty($billedAuditsIds)?$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")"):null;
            }
            if($this->input->get('carrier_current_audits', TRUE) == 1){
                $access_status_id = $this->MastersModel->getTableWiseColumnValue('nr_notify_settings', 'status_ids', array('type' => 'carrier_current_audits_status_ids'));
                $where .= "  AND nr_audit_status.status_id IN(".$access_status_id.") ";
                // $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(2);
                // !empty($billedAuditsIds)?$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")"):null;
            }
        }else if($this->currentUser['role_id']==5){
            if(!$this->input->get('testAudit', TRUE)){
                if($this->input->get('auditor_old_audits', TRUE) == 1){
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise(1);
                }else{
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
                }
                if($audit_ids){
                    $splitLarge =explode(',', $audit_ids);
                    if(sizeof($splitLarge)>50){
                        $newArray = array_chunk($splitLarge,50);
                        $where .= '(';
                        end($newArray);
                        $Lastkey = key($newArray);
                        foreach ($newArray as $key => $value) {
                            if($key == $Lastkey){
                                $where .="nr_audits.audit_id in (". implode(',',$value) .")";
                            }else{
                                $where .="nr_audits.audit_id in (". implode(',',$value) .") OR ";
                            }
                        }
                        $where .= ')';

                    }else{
                        $where .='nr_audits.audit_id IN ('.$audit_ids.') ';
                    }
                }
                //$where .= " AND nr_audits.status_id NOT IN (46) ";
                if($where == ""){
                    $where .= "nr_audits.status_id NOT IN (46) ";
                }else{
                    $where .= " AND nr_audits.status_id NOT IN (46) ";
                }
                $flag = 1;
            }
        }else if($this->currentUser['role_id'] == 6){
            if($flag == 1){
                $where .=" AND nr_audits.pick_by = '".$this->currentUser['user_id']."'"; 
            }else{
                $where .=" nr_audits.pick_by = '".$this->currentUser['user_id']."'";
                $flag = 1;
            }
        }else if($this->currentUser['role_id'] == 1){
            $users = $this->AutocompleteModel->getActiveUserRoleWise(6);
            $user_id = '';
            foreach ($users as $users_key => $users_value) {
                $user_id .= $users_value['id'].',';        
            }            
            $user_ids = substr ( $user_id , 0 , strlen($user_id) -1 );      
            $audit_ids_explode = explode(",", $user_ids);
            if($flag == 1){
                if(isset($postdata['status_id']) AND $postdata['status_id'] == 55){
                    $where .="nr_audits.pick_by in (". implode(',',$audit_ids_explode) .")";
                }
            }else{
                if(isset($postdata['status_id']) AND $postdata['status_id'] == 55){
                    $where .="nr_audits.pick_by in (". implode(',',$audit_ids_explode) .")";
                    $flag = 1;
                }
            }
        }
        if(isset($postdata['status_id'])){
            if($postdata['status_id'] == 1){
                $new_status_ids = $this->new_status_allow_audit_status();
                //$this->db2->where("nr_audits.status_id IN (".$new_status_ids['status_ids'].")");
                $where .= " nr_audits.status_id IN (".$new_status_ids['status_ids'].")";
            }else if($postdata['status_id'] == 24){//carrier Billed
                $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(2);//getting billed audits for carrier
                //$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")");
                $where .= " nr_audits.audit_id IN (".$billedAuditsIds.")";
            }else if($postdata['status_id'] == 25){//carrier Paid
                $paidAuditsIds = $this->getCarrierBilledPaidAuidtsIds(1);//getting paid audits for carrier
                $this->db2->where("nr_audits.audit_id IN (".$paidAuditsIds.")");
            }else{
                //$this->db2->where("nr_audits.status_id IN (".$postdata['status_id'].")");
                // if($flag == 1){
                //     $where .= " AND nr_audits.status_id IN (".$postdata['status_id'].")";
                // }else{
                //     $where .= " nr_audits.status_id IN (".$postdata['status_id'].")";
                // }
                if($postdata['status_id']!=55){
                    if($flag == 1){
                        $where .= " AND nr_audits.status_id IN (".$postdata['status_id'].")";
                    }else{
                        $where .= " nr_audits.status_id IN (".$postdata['status_id'].")";                    
                    }
                }
            }
        }else{
            if($this->input->get('freeze', TRUE) == 1){
                if($flag == 1){
                    $where .= " AND nr_audits.is_freeze = '1' ";
                }else{
                    $where .= " nr_audits.is_freeze = '1' ";
                }
            }
            if($this->input->get('testAudit', TRUE) == 1){
                if($this->currentUser['role_id']==8){
                    $where .= " AND nr_audits.is_test_audit = '1' ";
                }else{
                    if($flag == 1){
                        $where .= " AND nr_audits.is_test_audit = '1' ";
                    }else{
                        $where .= "nr_audits.is_test_audit = '1' ";
                    }
                }
                //$ids = $this->getInvoiceStatmentGeneratedIds();
                //$this->db2->where("nr_audits.audit_id NOT IN (".$ids.")");
                //$this->db2->where("nr_audits.status_id IN (".$postdata['status_id'].")");
                if($this->currentUser['role_id']==5){
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
                    if($audit_ids){
                        $splitLarge =explode(',', $audit_ids);
                        if(sizeof($splitLarge)>50){
                            $newArray = array_chunk($splitLarge,50);
                            $where .= '(';
                            end($newArray);
                            $Lastkey = key($newArray);
                            foreach ($newArray as $key => $value) {
                                if($key == $Lastkey){
                                    $where .="nr_audits.audit_id in (". implode(',',$value) .")";
                                }else{
                                    $where .="nr_audits.audit_id in (". implode(',',$value) .") OR ";
                                }
                            }
                            $where .= ')';

                        }else{
                            $where .=' AND nr_audits.audit_id IN ('.$audit_ids.') ';
                        }
                    }
                }
            }
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if($search!=""){
            if($where!=""){
                $where .= " AND ";
            }            
        }
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR NRU.first_name like '%".$search."%'
                    OR NRU.last_name like '%".$search."%'
                    OR nr_audit_status.status_text like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }
        $flag = 0;
        if(sizeof($postdata['filter_data'])){
                if($where!=""){
                    $where .= " AND ";
                }
                
                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
               
                if(isset($postdata['filter_data']['county'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_county like '%".$postdata['filter_data']['county']."%'";
                    }else{
                        $where .= "nr_address.r_county like '%".$postdata['filter_data']['county']."%'";
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['state'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                    }else{
                        $where .= "nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }                           
                }

                if(isset($postdata['filter_data']['reviewer_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits.pick_by= '".$postdata['filter_data']['reviewer_id']."'";
                    }else{
                        $where .= "nr_audits.pick_by='".$postdata['filter_data']['reviewer_id']."'";
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['source'])){
                    if($postdata['filter_data']['source'] == 0){
                       $source="Manual Entry";
                    }else if($postdata['filter_data']['source'] == 1){
                       $source="PDF Extraction";
                    }else if($postdata['filter_data']['source'] == 2){
                       $source="WEB Extraction";
                    }else if($postdata['filter_data']['source'] == 3){
                       $source="XML Extraction";
                    }

                    if($flag==1){
                        $where .=" AND nr_audits.source ='".$source."'";
                    }else{
                        $where .="nr_audits.source ='".$source."'";
                    }
                }
        }

        if($where!=''){
            $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $this->db2->where($where);
        }

        return $this->db2
        ->select('
                    count(nr_audits.audit_id) as tot,
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_reminders','nr_reminders.related_id=nr_audits.audit_id AND nr_reminders.related_type = "1" AND nr_reminders.user_id = ' . $user_id,'left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id and nr_audit_status.status_id !=15 and nr_audit_status.status_id !=20','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id = nr_audit_schedules.user_id', 'left')
        ->join('nr_users AS NRU','NRU.user_id = nr_audits.pick_by AND nr_audits.pick_by','left')
        ->join('nr_urgent_memo','nr_audits.audit_id=nr_urgent_memo.audit_id AND nr_urgent_memo.is_deleted=0','left')
        ->join('nr_auditor_invoice_details','nr_audits.audit_id=nr_auditor_invoice_details.audit_id','left')
        ->join('nr_carrier_statement_details','nr_audits.audit_id=nr_carrier_statement_details.audit_id','left')
        ->join('nr_states','(nr_address.r_state = nr_states.state_description OR nr_address.r_state = nr_states.state_name)','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
     * Description : Returns Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getPolicyListForStatusTot($postdata){
        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']==8){
            $where .= "  AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
            if($this->input->get('carrier_old_audits', TRUE) == 1){
                //only for carrier login paid
                $where .= "  AND nr_audit_status.status_id IN(24,45) ";
                // $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(1);//getting Paid audits for carrier
                // !empty($billedAuditsIds)?$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")"):null;
            }
            if($this->input->get('carrier_current_audits', TRUE) == 1){
                $access_status_id = $this->MastersModel->getTableWiseColumnValue('nr_notify_settings', 'status_ids', array('type' => 'carrier_current_audits_status_ids'));
                $where .= "  AND nr_audit_status.status_id IN(".$access_status_id.") ";
                // $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(2);
                // !empty($billedAuditsIds)?$this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")"):null;
            }
        }else if($this->currentUser['role_id']==5){
            if(!$this->input->get('testAudit', TRUE)){
                if($this->input->get('auditor_old_audits', TRUE) == 1){
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise(1);
                }else{
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
                }
                if($audit_ids){
                    $splitLarge =explode(',', $audit_ids);
                    if(sizeof($splitLarge)>50){
                        $newArray = array_chunk($splitLarge,50);
                        $where .= ' AND (';
                        end($newArray);
                        $Lastkey = key($newArray);
                        foreach ($newArray as $key => $value) {
                            if($key == $Lastkey){
                                $where .="nr_audits.audit_id in (". implode(',',$value) .")";
                            }else{
                                $where .="nr_audits.audit_id in (". implode(',',$value) .") OR ";
                            }
                        }
                        $where .= ')';
                    }else{
                        $where .=' AND nr_audits.audit_id IN ('.$audit_ids.') ';
                    }
                }
                //$where .= " AND nr_audits.status_id NOT IN (46) ";
                if($where == ""){
                    $where .= "nr_audits.status_id NOT IN (46) ";
                }else{
                    $where .= " AND nr_audits.status_id NOT IN (46) ";
                }
                //$this->db2->where("nr_audits.status_id NOT IN (46)",NULL, false);
            }
        }else if($this->currentUser['role_id'] == 6){
            $where .=" AND nr_audits.pick_by = '".$this->currentUser['user_id']."'";                
        }else if($this->currentUser['role_id'] == 1){
            $users = $this->AutocompleteModel->getActiveUserRoleWise(6);
            $user_id = '';
            foreach ($users as $users_key => $users_value) {
                $user_id .= $users_value['id'].',';        
            }            
            $user_ids = substr ( $user_id , 0 , strlen($user_id) -1 );      
            $audit_ids_explode = explode(",", $user_ids);
            if(isset($postdata['status_id']) AND $postdata['status_id'] == 55){
                $where .=" AND nr_audits.pick_by in (". implode(',',$audit_ids_explode) .")";
            }
        }
        if(isset($postdata['status_id'])){
            if($postdata['status_id'] == 1){
                $new_status_ids = $this->new_status_allow_audit_status();
                $this->db2->where("nr_audits.status_id IN (".$new_status_ids['status_ids'].")");
            }else if($postdata['status_id'] == 24){//carrier Billed
                $billedAuditsIds = $this->getCarrierBilledPaidAuidtsIds(2);//getting billed audits for carrier
                $this->db2->where("nr_audits.audit_id IN (".$billedAuditsIds.")");
            }else if($postdata['status_id'] == 25){//carrier Paid
                $paidAuditsIds = $this->getCarrierBilledPaidAuidtsIds(1);//getting paid audits for carrier
                $this->db2->where("nr_audits.audit_id IN (".$paidAuditsIds.")");
            }else{
                if($postdata['status_id'] != 55){
                    $this->db2->where("nr_audits.status_id IN (".$postdata['status_id'].")");
                }
            }
        }else{
            if($this->input->get('freeze', TRUE) == 1){
                $where .= " AND nr_audits.is_freeze = '1' ";
            }
            if($this->input->get('testAudit', TRUE) == 1){
                if($this->currentUser['role_id']==8){
                    $where .= " AND nr_audits.is_test_audit = '1' ";
                }else{
                    $where .= " AND nr_audits.is_test_audit = '1' ";
                }
                // $ids = $this->getInvoiceStatmentGeneratedIds();
                // $this->db2->where("nr_audits.audit_id NOT IN (".$ids.")");
                //$this->db2->where("nr_audits.status_id IN (".$postdata['status_id'].")");
                if($this->currentUser['role_id']==5){
                    $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
                    if($audit_ids){
                        $splitLarge =explode(',', $audit_ids);
                        if(sizeof($splitLarge)>50){
                            $newArray = array_chunk($splitLarge,50);
                            end($newArray);
                            $Lastkey = key($newArray);
                            foreach ($newArray as $key => $value) {
                                $this->db2->or_where_in("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
                            }

                        }else{
                            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
                        }
                    }
                }
            }
        }
        return $this->db2
        ->where($where)
        ->select('nr_audits.audit_id as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 and nr_address.related_type AND nr_address.is_primary=1','left')
        ->join('nr_audit_status', 'nr_audit_status.status_id=nr_audits.status_id', 'left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    public function disputeAuditNote($data) {
        $noteData['related_type'] = 28;
        $noteData['related_id'] = $data['audit_id'];
        $noteData['status'] = 1;
        $noteData['note'] = $data['note'];
        $noteResult = $this->InitDefaultModel->insertNote($noteData);
        return true;
    }
    public function cancelAuditNote($data) {
        $noteData['related_type'] = 27;
        $noteData['related_id'] = $data['audit_id'];
        $noteData['status'] = 1;
        $noteData['note'] = $data['note'];
        $noteResult = $this->InitDefaultModel->insertNote($noteData);
        return true;
    }
    public function archivedAuditNote($data) {
        $noteData['related_type'] = 31;
        $noteData['related_id'] = $data['audit_id'];
        $noteData['status'] = 1;
        $noteData['note'] = $data['note'];
        $noteResult = $this->InitDefaultModel->insertNote($noteData);
        return true;
    }

    /**
        * Description : get dispute policy list total as per filters for datatable
        * Note : 
    */
    public function getCancelPolicyListfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 20 AND nr_activity_logs.action_table = 'nr_audits' AND nr_activity_logs.action_column = 'status_id' AND nr_activity_logs.related_type = 1 AND nr_activity_logs.action_value = 20";
        if($this->currentUser['role_id']<=4){
        }else if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_activity_logs.action_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_activity_logs','nr_activity_logs.extras=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_activity_logs.action_by','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
        * Description : get total cancel policy for datatable
        * Note : 
    */
    public function getCancelPolicyListTot($postdata){

        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        $where .= " AND nr_audits.status_id = 20 AND nr_activity_logs.action_table = 'nr_audits' AND nr_activity_logs.action_column = 'status_id' AND nr_activity_logs.related_type = 1 AND nr_activity_logs.action_value = 20";

        if($this->currentUser['role_id']<=4){
        }else if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_activity_logs.action_by = ".$this->currentUser['user_id'];
        }

        return $this->db2
        ->where($where)
        ->select('count(nr_audits.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_activity_logs','nr_activity_logs.extras=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }

    /**
        * Description : get cancel policy list for datatable
        * Note : 
    */
    public function getCancelPolicyList($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 20 AND nr_activity_logs.action_table = 'nr_audits' AND nr_activity_logs.action_column = 'status_id' AND nr_activity_logs.related_type = 1 AND nr_activity_logs.action_value = 20";
        if($this->currentUser['role_id']<=4){
        }else if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_activity_logs.action_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    (select action_date from nr_activity_logs where extras = nr_audits.audit_id and action_table = "nr_audits" and action_column = "status_id" and action_value = 20 order by activity_log_id desc limit 1) as cancel_date,
                    (select CONCAT(u1.first_name," ",u1.last_name) from nr_activity_logs LEFT JOIN nr_users as u1 ON u1.user_id=nr_activity_logs.action_by where extras = nr_audits.audit_id and action_table = "nr_audits" and action_column = "status_id" and action_value = 20 order by activity_log_id desc limit 1) as cancelBy,
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id,
                    nr_activity_logs.action_date as cancel_date2,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS cancelBy2,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_activity_logs','nr_activity_logs.extras=nr_audits.audit_id AND nr_activity_logs.action_by != 0','left')
        ->join('nr_users','nr_users.user_id=nr_activity_logs.action_by','left')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get cancel policy list total as per filters for datatable
        * Note : 
    */
    public function getDisputePolicyListfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 15 AND nr_activity_logs.action_table = 'nr_audits' AND nr_activity_logs.action_column = 'status_id' AND nr_activity_logs.related_type = 1 AND nr_activity_logs.action_value = 15";
        if($this->currentUser['role_id']<=4){
        }else if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_activity_logs.action_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_activity_logs','nr_activity_logs.extras=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_activity_logs.action_by','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

     /**
        * Description : get total dispute policy for datatable
        * Note : 
    */
    public function getDisputePolicyListTot($postdata){

        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        $where .= " AND nr_audits.status_id = 15 AND nr_activity_logs.action_table = 'nr_audits' AND nr_activity_logs.action_column = 'status_id' AND nr_activity_logs.related_type = 1 AND nr_activity_logs.action_value = 15";

        if($this->currentUser['role_id']<=4){
        }else if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_activity_logs.action_by = ".$this->currentUser['user_id'];
        }

        return $this->db2
        ->where($where)
        ->select('count(nr_audits.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_activity_logs','nr_activity_logs.extras=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }

    /**
        * Description : get dispute policy list for datatable
        * Note : 
    */
    public function getDisputePolicyList($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 15 AND nr_activity_logs.action_table = 'nr_audits' AND nr_activity_logs.action_column = 'status_id' AND nr_activity_logs.related_type = 1 AND nr_activity_logs.action_value = 15";
        if($this->currentUser['role_id']<=4){
        }else if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_activity_logs.action_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    (select action_date from nr_activity_logs where extras = nr_audits.audit_id and action_table = "nr_audits" and action_column = "status_id" and action_value = 15 order by activity_log_id desc limit 1) as dispute_date,
                    (select CONCAT(u1.first_name," ",u1.last_name) from nr_activity_logs LEFT JOIN nr_users as u1 ON u1.user_id=nr_activity_logs.action_by where extras = nr_audits.audit_id and action_table = "nr_audits" and action_column = "status_id" and action_value = 15 order by activity_log_id desc limit 1) as disputeBy,
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_activity_logs.action_date as dispute_date2,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS disputeBy2,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_activity_logs','nr_activity_logs.extras=nr_audits.audit_id AND nr_activity_logs.action_by != 0','left')
        ->join('nr_users','nr_users.user_id=nr_activity_logs.action_by','left')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }


     /**
     * Description : Returns Audit Statuses List For Autocomplete 
     * Note : Records will depend on the passed 'term' parameter & will return records with is_active = 1
    */
    function getAuditStatus($data){

        $search = $this->input->get('term');
        if(isset($search['term'])){
            $this->db2->like('status_text',$search['term']);
        }
        if(isset($search['page_limit'])){
            $this->db2->limit($search['page_limit']);
        }
        $this->db2->where('is_active','1');
        $this->db2->where('status_sort != ',0);
        $this->db2->order_by('status_sort','asc');
        return $this->db2
        ->select('status_id as id,status_id,status_text as name')
        ->get('nr_audit_status')->result_array();
    }

    public function getAuditStatusHeatMapFilter(){
        $status_ids = $this->db2->select('id,status_ids')
                  ->where('type','scheduler_policy_ids')
                  ->get('nr_notify_settings')
                  ->row_array();
        
        return $this->db2->select('status_id,status_text')
                  ->where('is_active',1)
                  ->where_in('status_id', explode(',',$status_ids['status_ids']))
                  ->get('nr_audit_status')
                  ->result_array();
    }

    public function getRecentlyVisited(){

        $page = $this->input->get('page');
        $show_per_page = $this->input->get('show_per_page');
        $where = "";
        if($this->currentUser['role_id']==1){
            $user_id = $this->input->get('user_id');
            if($user_id!="" and $user_id!='undefined' and $user_id!='null'){
                $where.="(nr_audit_schedules.user_id= $user_id) AND ";
            }
        }else{
            $user_id = $this->currentUser['user_id'];
            $where.="(nr_audit_schedules.user_id= $user_id) AND ";
        }

        $where.='(nr_audits.status_id in (7,8,9,10))';
       
        $this->db2->where($where);

        $resulData = $this->db2
            ->select('
                    nr_audit_schedules.audit_id, nr_audit_schedules.schedule_from_time, nr_audit_schedules.schedule_to_time, nr_audit_schedules.is_reschedule, nr_audit_schedules.reason, nr_audit_schedules.schedule_date,
                    APD.policy_number,
                    AID.insured_name
                ')
            ->from('nr_audit_schedules')
            ->join('nr_audits','nr_audits.audit_id=nr_audit_schedules.audit_id','left')
            ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audit_schedules.audit_id','left')
            ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audit_schedules.audit_id','left')
            ->get()
            ->result_array();
        $data['total'] = sizeof($resulData);
        $data['records'] = pagination($resulData, $page, $show_per_page);
        return $data;
    }

    public function getDisputeAudit(){

        $page = $this->input->get('page');
        $show_per_page = $this->input->get('show_per_page');
        $where = "";
        if($this->currentUser['role_id']==1){
            $user_id = $this->input->get('user_id');
            if($user_id!="" and $user_id!='undefined' and $user_id!='null'){
                $where.="(nr_audit_schedules.user_id= $user_id) AND ";
            }
        }else{
            $user_id = $this->currentUser['user_id'];
            $where.="(nr_audit_schedules.user_id= $user_id) AND ";
        }

        $where.='(nr_audits.status_id in (15,14))';
       
        $this->db2->where($where);

        $resulData = $this->db2
            ->select('
                    nr_audit_schedules.audit_id, nr_audit_schedules.schedule_from_time, nr_audit_schedules.schedule_to_time, nr_audit_schedules.is_reschedule, nr_audit_schedules.reason, nr_audit_schedules.schedule_date,
                    APD.policy_number,
                    AID.insured_name
                ')
            ->from('nr_audit_schedules')
            ->join('nr_audits','nr_audits.audit_id=nr_audit_schedules.audit_id','left')
            ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audit_schedules.audit_id','left')
            ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audit_schedules.audit_id','left')
            ->get()
            ->result_array();
        $data['total'] = sizeof($resulData);
        $data['records'] = pagination($resulData, $page, $show_per_page);
        return $data;
    }

    public function getSidebarPolicy(){

        $page = $this->input->get('page');
        $reqeustType = $this->input->get('reqeustType');
        /*
            $reqeustType = 1 OverDue
            $reqeustType = 2 Today
            $reqeustType = 3 Upcoming
        */
        $show_per_page = $this->input->get('show_per_page');
        $where = "";
        if($this->currentUser['role_id']==1){
            $user_id = $this->input->get('user_id');
            if($user_id!="" and $user_id!='undefined' and $user_id!='null'){
                $where.="(nr_audit_schedules.user_id= $user_id) AND ";
            }
        }else{
            $user_id = $this->currentUser['user_id'];
            $where.="(nr_audit_schedules.user_id= $user_id) AND ";
        }

        $today = strtotime(date("Y-m-d"));
        $tomorrow = strtotime(date("Y-m-d", strtotime('tomorrow')));

        switch ($reqeustType) {
            case 1:
                $where.='('.$today.' >  nr_audit_schedules.schedule_date)';
                break;
            case 2:
                $where.='('.$today.' =  nr_audit_schedules.schedule_date)';
                break; 
            case 3:
                $where.='('.$today.' <  nr_audit_schedules.schedule_date)';
                break;
            default:
                exit;
                break;
        }
       
        $this->db2->where($where);

        $resulData = $this->db2
            ->select('
                    nr_audit_schedules.audit_id, nr_audit_schedules.schedule_from_time, nr_audit_schedules.schedule_to_time, nr_audit_schedules.is_reschedule, nr_audit_schedules.reason, nr_audit_schedules.schedule_date,
                    APD.policy_number,
                    AID.insured_name
                ')

            ->from('nr_audit_schedules')
            ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audit_schedules.audit_id','left')
            ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audit_schedules.audit_id','left')
            ->get()
            ->result_array();
        $data['total'] = sizeof($resulData);
        $data['records'] = pagination($resulData, $page, $show_per_page);
        return $data;
    }

    public function auditPickBy($data) {
        $where['audit_id'] = $data['audit_id'];
        $nr_audit_status = [];
        $nr_audit_status['audit_id'] = $data['audit_id'];
        if($data['pick']){
            $dataUpdate['pick_by'] = $this->currentUser['user_id'];
            $nr_audit_status['status_id'] = 41;//change status to Reviewer assigned
            $return = 676;
        }else{
            $get_audit_last_status = $this->db2
                ->select('*')
                ->from('nr_activity_logs')
                ->where('action_extras',$data['audit_id'])
                ->where('action_column','status_id')
                ->where('action_table','nr_audits')
                ->order_by('activity_log_id','desc')
                ->limit(1)
                ->get()
                ->row_array();
            $nr_audit_status['status_id'] = $get_audit_last_status['action_value_old'];//If drop the audit send to previous status
            $dataUpdate['pick_by'] = '';
            $return = 677;
        }
        $this->AuditsModel->auditIdsStatusChange($nr_audit_status);
        $this->db2->update('nr_audits',$dataUpdate,$where);
        //get old user by assing audit in log history
        $getData = $this->db2->select('*')->from('nr_activity_logs')->where('action_column','pick_by')->where('action_table','nr_audits')->where('action','changed')->where('extras',$data['audit_id'])->order_by('activity_log_id','desc')->get()->row_array();
        if(empty($getData)){
            $old_users_id = 0;
        }else{
            $old_users_id = $getData['action_value'];
        }
        if($this->currentUser['role_id'] == 6 || $this->currentUser['role_id'] == 1 || $this->currentUser['role_id'] == 2 || $this->currentUser['role_id'] == 3 || $this->currentUser['role_id'] == 4 || $this->currentUser['role_id'] == 7){
            $dates['created_date'] = gettimestamp();
            $dates['created_host'] = NRM_HOST_ADDRESS;
            $history['action'] = 'changed';
            $history['action_column'] = 'pick_by';
            $history['action_table'] = 'nr_audits';
            $history['action_value'] = $data['pick']==0?0:$this->currentUser['user_id'];
            $history['action_value_old'] = $old_users_id;
            $history['action_extras'] = $data['audit_id'];
            $history['extras'] = $data['audit_id'];
            $history['related_type'] = 1;
            $history['action_by'] = $this->currentUser['user_id'];
            $history['action_date'] = $dates['created_date'];
            $history['action_host'] = $dates['created_host'];
            $this->db2->insert('nr_activity_logs',$history);
        }
        return $return;
    }

    /**
        * Description : get total schedule policy for datatable
        * Note : 
    */
    public function getSchedulePolicyListAuditorWiseTot($postdata){

        $where ='';

        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }
        $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audit_schedules.schedule_id is not null";
        }else{
            $where .= " AND nr_audit_schedules.user_id = ".$this->currentUser['user_id'];
        }

        return $this->db2
        ->where($where)
        ->select('count(nr_audit_schedules.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }

    /**
        * Description : get schedule policy list for datatable
        * Note : 
    */
    public function getSchedulePolicyListAuditorWise($postdata){
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
            if(isset($postdata['filter_data']['schedule_date'])){
                $where .= "(";
                $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                $where .= " nr_audit_schedules.schedule_date =".$schedule_date;
                $where .= " OR nr_tentative.schedule_date =".$schedule_date;

                $where .= ")";
                $flag = 1;
            }
            
            if(isset($postdata['filter_data']['user_id'])){
                if($flag==1){
                    $where .= " AND ( nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'] .')';
                }else{
                    $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['user_id2'])){
                if($flag==1){
                    $where .= " AND ( nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'] .')';
                }else{
                    $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['tag_id'])){
                if($flag==1){
                    $where .= " AND ( nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'] .')';
                }else{
                    $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['carrier_id'])){
                if($flag==1){
                    $where .= " AND ( CM.carrier_id =".$postdata['filter_data']['carrier_id'] .')';
                }else{
                    $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['zipcode'])){
                if($flag==1){
                    $where .= " AND ( nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                }else{
                    $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['search_type'])){
                if($postdata['filter_data']['search_type'] == 1){
                    if($flag==1){
                        $where .= " AND ( nr_address.r_address like '%".$postdata['filter_data']['location']['r_address']."%'";
                    }else{
                        $where .= "nr_address.r_address like '%".$postdata['filter_data']['location']['r_address']."%'";
                        $flag=1;
                    }    
                }
                if($postdata['filter_data']['search_type'] == 2){
                    if($flag==1){
                        $where .= " AND ( nr_address.r_county like '%".$postdata['county_name']."%'";    
                    }else{
                        $where .= "nr_address.r_county like '%".$postdata['county_name']."%'";
                        $flag=1;    
                    }    
                }
                if($postdata['filter_data']['search_type'] == 3){
                    if($flag==1){
                        $where .= " AND ( nr_address.r_state like '%".$postdata['filter_data']['location']['r_state']."%'";
                    }else{
                        $where .= "nr_address.r_state like '%".$postdata['filter_data']['location']['r_state']."%'";
                        $flag=1;
                    }    
                }
                if($postdata['filter_data']['search_type'] == 4){
                    if($flag==1){
                        $where .= " AND ( nr_address.r_city like '%".$postdata['filter_data']['location']['r_city']."%'";
                    }else{
                        $where .= "nr_address.r_city like '%".$postdata['filter_data']['location']['r_city']."%'";
                        $flag=1;
                    }    
                }
                if($postdata['filter_data']['search_type'] == 5){
                    if($flag==1){
                        $where .= " AND ( nr_address.r_zipcode like '%".$postdata['filter_data']['location']['r_zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['location']['r_zipcode']."%'";
                        $flag=1;
                    }    
                }
            }

            if(isset($postdata['filter_data']['seeAllUnscheduled'])){
                if($flag==1){
                    $where .= " AND nr_audit_schedules.schedule_id is null";
                    $where .= " AND nr_audits.audit_id IN (".$postdata['audit_id'].")";
                }else{
                    $where .= "nr_audit_schedules.schedule_id is null";
                    $where .= " AND nr_audits.audit_id IN (".$postdata['audit_id'].")";
                    $flag=1;
                }
            }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audit_schedules.schedule_id is not null";
        }else{
            $where .= " AND nr_audit_schedules.user_id = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id, link,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.policy_period_from,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,r_city,r_state,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_scheduled_routes','nr_scheduled_routes.auditor_id=nr_audit_schedules.user_id AND nr_scheduled_routes.schedule_date=nr_audit_schedules.schedule_date','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get schedule policy list total as per filters for datatable
        * Note : 
    */
    public function getSchedulePolicyListAuditorWisefilterTot($postdata){
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
            if(isset($postdata['filter_data']['schedule_date'])){

                $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                $flag = 1;
            }
            
            if(isset($postdata['filter_data']['user_id'])){
                if($flag==1){
                    $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                }else{
                    $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['user_id2'])){
                if($flag==1){
                    $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                }else{
                    $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['tag_id'])){
                if($flag==1){
                    $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                }else{
                    $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['carrier_id'])){
                if($flag==1){
                    $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                }else{
                    $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['zipcode'])){
                if($flag==1){
                    $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                }else{
                    $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['search_type'])){
                if($postdata['filter_data']['search_type'] == 1){
                    if($flag==1){
                        $where .= " AND nr_address.r_address like '%".$postdata['filter_data']['location']['r_address']."%'";
                    }else{
                        $where .= "nr_address.r_address like '%".$postdata['filter_data']['location']['r_address']."%'";
                        $flag=1;
                    }    
                }
                if($postdata['filter_data']['search_type'] == 2){
                    if($flag==1){
                        $where .= " AND nr_address.r_county like '%".$postdata['county_name']."%'";    
                    }else{
                        $where .= "nr_address.r_county like '%".$postdata['county_name']."%'";
                        $flag=1;    
                    }      
                }
                if($postdata['filter_data']['search_type'] == 3){
                    if($flag==1){
                        $where .= " AND nr_address.r_state like '%".$postdata['filter_data']['location']['r_state']."%'";
                    }else{
                        $where .= "nr_address.r_state like '%".$postdata['filter_data']['location']['r_state']."%'";
                        $flag=1;
                    }    
                }
                if($postdata['filter_data']['search_type'] == 4){
                    if($flag==1){
                        $where .= " AND nr_address.r_city like '%".$postdata['filter_data']['location']['r_city']."%'";
                    }else{
                        $where .= "nr_address.r_city like '%".$postdata['filter_data']['location']['r_city']."%'";
                        $flag=1;
                    }    
                }
                if($postdata['filter_data']['search_type'] == 5){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['location']['r_zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['location']['r_zipcode']."%'";
                        $flag=1;
                    }    
                }
            }

            if(isset($postdata['filter_data']['seeAllUnscheduled'])){
                if($flag==1){
                    $where .= " AND nr_audit_schedules.schedule_id is null";
                    $where .= " AND nr_audits.audit_id IN (".$postdata['audit_id'].")";
                }else{
                    $where .= "nr_audit_schedules.schedule_id is null";
                    $where .= " AND nr_audits.audit_id IN (".$postdata['audit_id'].")";
                    $flag=1;
                }
            }

            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audit_schedules.schedule_id is not null";
        }else{
            $where .= " AND nr_audit_schedules.user_id = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);
        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.policy_period_from,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    public function getPoboxLocationPolicyListTot($postdata){
        $where ='';
        $po_box_case1 = 'P.O. Box';
        $po_box_case2 = 'P.O.Box';
        $po_box_case3 = 'PO Box';
        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }

        $where .= "AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1 AND nr_address.r_address like '%".$po_box_case1."%' OR nr_address.r_address like '%".$po_box_case2."%' OR nr_address.r_address like '%".$po_box_case3."%' OR AID.insured_name like '%".$po_box_case1."%' OR AID.insured_name like '%".$po_box_case2."%' OR AID.insured_name like '%".$po_box_case3."%'";

        return $this->db2
        ->where($where)
        ->select('count(nr_audits.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->get()
        ->result_array(); 
    }

    public function getPoboxLocationPolicyListfilterTot($postdata){
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';
        $po_box_case1 = 'P.O. Box';
        $po_box_case2 = 'P.O.Box';
        $po_box_case3 = 'PO Box';
        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_city like '%".$search."%'
                OR r_state like '%".$search."%'
                OR r_country like '%".$search."%'
                OR r_county like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1) AND (nr_address.r_address like '%".$po_box_case1."%' OR nr_address.r_address like '%".$po_box_case2."%' OR nr_address.r_address like '%".$po_box_case3."%' OR AID.insured_name like '%".$po_box_case1."%' OR AID.insured_name like '%".$po_box_case2."%' OR AID.insured_name like '%".$po_box_case3."%')";

        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id, 
                    AID.insured_name,
                    APD.policy_number, APD.policy_period_to,
                    r_address,r_lat,r_lon,r_zipcode,r_city,r_state
                ')
        ->from('nr_audits')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    public function getPoboxLocationPolicyList($postdata){
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';
        $po_box_case1 = 'P.O. Box';
        $po_box_case2 = 'P.O.Box';
        $po_box_case3 = 'PO Box';
        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_city like '%".$search."%'
                OR r_state like '%".$search."%'
                OR r_country like '%".$search."%'
                OR r_county like '%".$search."%'
                )
            ";
        }
        $flag = 0;
        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }
        }
        if($where!=''){
            $where .= " AND ";
        }      

        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1) AND (nr_address.r_address like '%".$po_box_case1."%' OR nr_address.r_address like '%".$po_box_case2."%' OR nr_address.r_address like '%".$po_box_case3."%' OR AID.insured_name like '%".$po_box_case1."%' OR AID.insured_name like '%".$po_box_case2."%' OR AID.insured_name like '%".$po_box_case3."%')";

        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id, 
                    AID.insured_name,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    r_address,r_lat,r_lon,r_zipcode,r_city,r_state,r_country,r_county
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

     /**
        * Description : get total visited policy for datatable
        * Note : 
    */
    public function getVisitedAuditsAllTot($postdata){
        $where ='';

        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }
        $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        $where .= " AND nr_audits.status_id = 7";
        $where .=" AND nr_audits.pick_by != ''";

        return $this->db2
        ->where($where)
        ->select('count(nr_audit_schedules.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }
    
    /**
        * Description : get visited policy list for datatable
        * Note : 
    */
    public function getVisitedAuditsAll($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        // $where .= " AND nr_audits_tags.tag_id IN (".implode(',', $postdata['filter_data']['tag_id']).")";
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        // $where .= " nr_audits_tags.tag_id IN (".implode(',', $postdata['filter_data']['tag_id']).")";
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 7";
        $where .=" AND nr_audits.pick_by != ''";
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    CONCAT(pickby.first_name , " " , pickby.last_name) AS pickbyName,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as pickby','pickby.user_id=nr_audits.pick_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get visited policy list total as per filters for datatable
        * Note : 
    */
    public function getVisitedAuditsAllfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 7";
        $where .=" AND nr_audits.pick_by != ''";
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    CONCAT(pickby.first_name , " " , pickby.last_name) AS pickbyName,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as pickby','pickby.user_id=nr_audits.pick_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
        * Description : get total visited policy for datatable
        * Note : 
    */
    public function getAllOpenReviewAuditsTot($postdata){

        $where ='';

        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }
        $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        //$where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        $review_pool_status_ids = $this->myReviewPoolAllowStatusIds();
        $nonCoopComplateAuditIds = $this->nonCoopWithComplateAudit($search="",$postdata['filter_data']="");
        $where .= " AND nr_audits.status_id IN (".implode(',',$review_pool_status_ids).")";
        if($postdata['mypick']==1){
            $where .=" AND nr_audits.pick_by = ".$this->currentUser['user_id'];
        }else{
            $where .=" AND nr_audits.pick_by = ''";
        }
        if($nonCoopComplateAuditIds!=null AND $postdata['mypick']==0){
            $where .= " OR nr_audits.audit_id IN (".implode(',',$nonCoopComplateAuditIds).")";
        }
        return $this->db2
        ->where($where)
        //->select('count(nr_audit_schedules.audit_id) as tot')
        ->select('count(nr_audits.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }

    /**
        * Description : get visited policy list for datatable
        * Note : 
    */
    public function getAllOpenReviewAudits($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $review_pool_status_ids = $this->myReviewPoolAllowStatusIds();
        $nonCoopComplateAuditIds = $this->nonCoopWithComplateAudit($search,$postdata['filter_data']);
            $where .= " AND nr_audits.status_id IN (".implode(',',$review_pool_status_ids).")";
        if($postdata['mypick']==1){
            $where .=" AND nr_audits.pick_by = ".$this->currentUser['user_id'];
        }else{
            $where .=" AND nr_audits.pick_by = ''";
        }
        if($search=="" || $postdata['filter_data'] == null){
            if($nonCoopComplateAuditIds!=null AND $postdata['mypick'] == 0){
                $where .= " OR nr_audits.audit_id IN (".implode(',',$nonCoopComplateAuditIds).")";
            }
        }        
        $this->db2->where($where);
       
        return $this->db2
        ->select('
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id, nr_audits.is_non_coop,nr_audits.modified_date,nr_audits.source,
                    nr_contacts.contact AS phone_number,
                    nr_audit_type.audit_type_text,
                    nr_audit_status.status_text,
                    nr_audit_status.color_code,
                    nr_audit_group.group_name AS audit_group_name,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    nr_audits_xml_details.control_id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS name,
                    CONCAT(NRU.first_name," ",NRU.last_name) as pick_by_name, 
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,r_county,r_city,r_country,r_state,
                    nr_urgent_memo.notes,nr_urgent_memo.memo_id,nr_urgent_memo.created_by AS creator_id,
                    nr_urgent_memo.is_deleted AS urgent_mome_is_delete,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags,
                    nr_auditor_invoice_details.created_date as invoice_created_date,
                    CONCAT(nr_states.state_name , "-" , nr_states.state_description) AS state_name
                ')
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id and nr_audit_status.status_id !=15 and nr_audit_status.status_id !=20','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_audit_group','nr_audit_group.group_id=nr_audits.audit_group_id','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_urgent_memo','nr_audits.audit_id=nr_urgent_memo.audit_id AND nr_urgent_memo.is_deleted=0','left')
        ->join('nr_auditor_invoice_details','nr_audits.audit_id=nr_auditor_invoice_details.audit_id','left')
        ->join('nr_users AS NRU','NRU.user_id = nr_audits.pick_by AND nr_audits.pick_by','left')
        ->join('nr_states','nr_address.r_state = nr_states.state_description OR nr_address.r_state = nr_states.state_name AND (nr_address.related_type=6)','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get visited policy list total as per filters for datatable
        * Note : 
    */
    public function getAllOpenReviewAuditsfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $review_pool_status_ids = $this->myReviewPoolAllowStatusIds();
        $nonCoopComplateAuditIds = $this->nonCoopWithComplateAudit($search,$postdata['filter_data']);
            $where .= " AND nr_audits.status_id IN (".implode(',',$review_pool_status_ids).")";
        if($postdata['mypick']==1){
            $where .=" AND nr_audits.pick_by = ".$this->currentUser['user_id'];
        }else{
            $where .=" AND nr_audits.pick_by = ''";
        }
        if($search=="" || $postdata['filter_data'] == null){
            if($nonCoopComplateAuditIds!=null AND $postdata['mypick'] == 0){
                $where .= " OR nr_audits.audit_id IN (".implode(',',$nonCoopComplateAuditIds).")";
            }
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id
                ')
        ->from('nr_audits')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id and nr_audit_status.status_id !=15 and nr_audit_status.status_id !=20','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_audit_group','nr_audit_group.group_id=nr_audits.audit_group_id','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_urgent_memo','nr_audits.audit_id=nr_urgent_memo.audit_id AND nr_urgent_memo.is_deleted=0','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    public function getQuestionWhereGroupBy($flag=1) {
        if($flag==1){//All review questions which is not answered
            return " AND nr_audits.audit_id in (SELECT `audit_id` FROM `nr_audit_questions_answers` WHERE answer='' GROUP BY `audit_id`)";
        }else if($flag==2){//my review quetion which is not answered
            if($this->currentUser['role_id'] == 5){
                return " AND nr_audits.audit_id in (SELECT `audit_id` FROM `nr_audit_questions_answers` WHERE modified_by = ".$this->currentUser['user_id']." AND answer='' GROUP BY `audit_id`)";
            }else{
                return " AND nr_audits.audit_id in (SELECT `audit_id` FROM `nr_audit_questions_answers` WHERE created_by = ".$this->currentUser['user_id']." AND answer='' GROUP BY `audit_id`)";
            }
        }else if($flag==3){//All quetion which is answered
            return " AND nr_audits.audit_id in (SELECT `audit_id` FROM `nr_audit_questions_answers` WHERE answer <>'' GROUP BY `audit_id`)";
        }else if($flag==4){//my quetion which is answered
            if($this->currentUser['role_id'] == 5){
                return " AND nr_audits.audit_id in (SELECT `audit_id` FROM `nr_audit_questions_answers` WHERE modified_by = ".$this->currentUser['user_id']." AND answer<>'' GROUP BY `audit_id`)";
            }else{
                return " AND nr_audits.audit_id in (SELECT `audit_id` FROM `nr_audit_questions_answers` WHERE created_by = ".$this->currentUser['user_id']." AND answer<>'' GROUP BY `audit_id`)";
            }
        }else{
            echo "Not Valid";
        }
    }

    /**
     * Description : Returns Audit list in the form which datatable expects
     * Note : 
    */
    public function getAllReviewQuestionsAudits($postdata){
        $where ='';

        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }

        if($where!=''){
            $where .= "AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy();
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy();
            $this->db2->where($where);
        }
        if($length!=-1){
            $this->db2->limit($length,$start);
        }
        return $this->db2
            ->select('
                    schedule_id, fed_id,
                    nr_audits.audit_id,nr_audits.created_date,nr_audits.source,nr_audits.is_active,nr_audits.audit_group_id,nr_audits.is_primary_in_group,nr_audits.is_non_coop,nr_audits.status_id,
                    nr_audits.modified_date,
                    APD.policy_number, APD.policy_period_to,
                    APD.created_date as policy_created_date,
                    AID.insured_name,
                    nr_contacts.contact AS phone_number,
                    cm.carrier_name,
                    nr_audit_type.audit_type_text,
                    nr_address.r_lat,nr_address.r_lon,nr_address.r_country,r_county,nr_address.r_city,nr_address.r_zipcode,nr_address.r_state,nr_address.r_address,
                    nr_audit_status.status_text,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    nr_audits_xml_details.control_id,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->order_by($column_name,$order)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
     * Description : Returns Filtered Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getAllReviewQuestionsAuditsFilterTot($postdata){
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }
        if($where!=''){
            $where .= "AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy();
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy();
            $this->db2->where($where);
        }
        $result = $this->db2
        ->select('
                    count(nr_audits.audit_id) as tot,
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
        return $result;
    }

    /**
     * Description : Returns Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getAllReviewQuestionsAuditsTot($postdata){
        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $where .= $this->getQuestionWhereGroupBy();
        return $this->db2
        ->where($where)
        ->select('nr_audits.audit_id as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 and nr_address.related_type AND nr_address.is_primary=1','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    public function getAllAnsweredQuestionsAudits($postdata){
        $where ='';

        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }

        if($where!=''){
            $where .= "AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(3);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(3);
            $this->db2->where($where);
        }
        if($length!=-1){
            $this->db2->limit($length,$start);
        }
        return $this->db2
            ->select('
                    schedule_id, fed_id,
                    nr_audits.audit_id,nr_audits.created_date,nr_audits.source,nr_audits.is_active,nr_audits.audit_group_id,nr_audits.is_primary_in_group,nr_audits.is_non_coop,nr_audits.status_id,
                    nr_audits.modified_date,
                    APD.policy_number, APD.policy_period_to,
                    APD.created_date as policy_created_date,
                    AID.insured_name,
                    nr_contacts.contact AS phone_number,
                    cm.carrier_name,
                    nr_audit_type.audit_type_text,
                    nr_address.r_lat,nr_address.r_lon,nr_address.r_country,r_county,nr_address.r_city,nr_address.r_zipcode,nr_address.r_state,nr_address.r_address,
                    nr_audit_status.status_text,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    nr_audits_xml_details.control_id,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->order_by($column_name,$order)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
     * Description : Returns Filtered Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getAllAnsweredQuestionsAuditsFilterTot($postdata){
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }
        if($where!=''){
            $where .= "AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(3);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(3);
            $this->db2->where($where);
        }
        $result = $this->db2
        ->select('
                    count(nr_audits.audit_id) as tot,
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
        return $result;
    }

    /**
     * Description : Returns Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getAllAnsweredQuestionsAuditsTot($postdata){
        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $where .= $this->getQuestionWhereGroupBy(3);
        return $this->db2
        ->where($where)
        ->select('nr_audits.audit_id as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 and nr_address.related_type AND nr_address.is_primary=1','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    public function getMyAnsweredQuestionsAudits($postdata){
        $where ='';

        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }

        $flag = 0;

        if(sizeof($postdata['filter_data'])){
            if(isset($postdata['filter_data']['state'])){
                if($flag==1){
                    $where .= " AND nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                }else{
                    $where .= "nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['carrier_id'])){
                if($flag==1){
                    $where .= " AND cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                }else{
                    $where .= " cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['zipcode'])){
                if($flag==1){
                    $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                }else{
                    $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['source'])){
                if($postdata['filter_data']['source'] == 0){
                   $source="Manual Entry";
                }else if($postdata['filter_data']['source'] == 1){
                   $source="PDF Extraction";
                }else if($postdata['filter_data']['source'] == 2){
                   $source="WEB Extraction";
                }else if($postdata['filter_data']['source'] == 3){
                   $source="XML Extraction";
                }

                if($flag==1){
                    $where .=" AND nr_audits.source ='".$source."'";
                }else{
                    $where .="nr_audits.source ='".$source."'";
                }
            }
        }
        if($where!=''){
            $where .= " AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(4);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(4);
            $this->db2->where($where);
        }
        $this->db2->where('nr_carrier_statement_details.audit_id is NULL');
        if($length!=-1){
            $this->db2->limit($length,$start);
        }
        return $this->db2
            ->select('
                    schedule_id, fed_id,
                    nr_audits.audit_id,nr_audits.created_date,nr_audits.source,nr_audits.is_active,nr_audits.audit_group_id,nr_audits.is_primary_in_group,nr_audits.is_non_coop,nr_audits.status_id,
                    nr_audits.modified_date,
                    APD.policy_number, APD.policy_period_to,
                    APD.created_date as policy_created_date,
                    AID.insured_name,
                    nr_contacts.contact AS phone_number,
                    cm.carrier_name,
                    nr_audit_type.audit_type_text,
                    nr_address.r_lat,nr_address.r_lon,nr_address.r_country,r_county,nr_address.r_city,nr_address.r_zipcode,nr_address.r_state,nr_address.r_address,
                    nr_audit_status.status_text,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    nr_audits_xml_details.control_id,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags,
                    CONCAT(nr_states.state_name , "-" , nr_states.state_description) AS state_name
                ')
        ->order_by($column_name,$order)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_statement_details','nr_audits.audit_id=nr_carrier_statement_details.audit_id','left')
        ->join('nr_states','nr_address.r_state = nr_states.state_description OR nr_address.r_state = nr_states.state_name AND (nr_address.related_type=6)','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
     * Description : Returns Filtered Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getMyAnsweredQuestionsAuditsFilterTot($postdata){
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }
        $flag=0;

        if(sizeof($postdata['filter_data'])){
            if(isset($postdata['filter_data']['state'])){
                if($flag==1){
                    $where .= " AND nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                }else{
                    $where .= "nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['carrier_id'])){
                if($flag==1){
                    $where .= " AND cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                }else{
                    $where .= "cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['zipcode'])){
                if($flag==1){
                    $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                }else{
                    $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['source'])){
                if($postdata['filter_data']['source'] == 0){
                   $source="Manual Entry";
                }else if($postdata['filter_data']['source'] == 1){
                   $source="PDF Extraction";
                }else if($postdata['filter_data']['source'] == 2){
                   $source="WEB Extraction";
                }else if($postdata['filter_data']['source'] == 3){
                   $source="XML Extraction";
                }

                if($flag==1){
                    $where .=" AND nr_audits.source ='".$source."'";
                }else{
                    $where .="nr_audits.source ='".$source."'";
                }
            }
        }

        if($where!=''){
            $where .= " AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(4);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(4);
            $this->db2->where($where);
        }
        $this->db2->where('nr_carrier_statement_details.audit_id is NULL');
        $result = $this->db2
        ->select('
                    count(nr_audits.audit_id) as tot,
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_statement_details','nr_audits.audit_id=nr_carrier_statement_details.audit_id','left')
        ->join('nr_states','nr_address.r_state = nr_states.state_description OR nr_address.r_state = nr_states.state_name AND (nr_address.related_type=6)','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
        return $result;
    }

    /**
     * Description : Returns Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getMyAnsweredQuestionsAuditsTot($postdata){
        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $where .= $this->getQuestionWhereGroupBy(4);
        $this->db2->where('nr_carrier_statement_details.audit_id is NULL');
        return $this->db2
        ->where($where)
        ->select('nr_audits.audit_id as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 and nr_address.related_type AND nr_address.is_primary=1','left')
        ->join('nr_carrier_statement_details','nr_audits.audit_id=nr_carrier_statement_details.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }


    public function getMyAnsweredQuestionsCsv($postdata){
        $format = getDateFormatInSql($this->companySetting->dateformat);
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $flag = 0;
        if(sizeof($postdata['filter_data'])){
            
            if(isset($postdata['filter_data']['state'])){
                if($flag==1){
                    $where .= " AND nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                }else{
                    $where .= "nr_address.r_state in (".arrayElementToSting($postdata['filter_data']['state']).")";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['carrier_id'])){
                if($flag==1){
                    $where .= " AND cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                }else{
                    $where .= " cm.carrier_id =".$postdata['filter_data']['carrier_id'];
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['zipcode'])){
                if($flag==1){
                    $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                }else{
                    $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    $flag=1;
                }
            }

            if(isset($postdata['filter_data']['source'])){
                if($postdata['filter_data']['source'] == 0){
                   $source="Manual Entry";
                }else if($postdata['filter_data']['source'] == 1){
                   $source="PDF Extraction";
                }else if($postdata['filter_data']['source'] == 2){
                   $source="WEB Extraction";
                }else if($postdata['filter_data']['source'] == 3){
                   $source="XML Extraction";
                }

                if($flag==1){
                    $where .=" AND nr_audits.source ='".$source."'";
                }else{
                    $where .="nr_audits.source ='".$source."'";
                }
            }
        }
        if($where!=''){
            $where .= " AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(4);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(4);
            $this->db2->where($where);
        }
        $this->db2->where('nr_carrier_statement_details.audit_id is NULL');
        $site = $this->UserProfileModel->GetSubdomain();
        $this->load->dbutil();
        $this->load->helper('file');
        $this->load->helper('download');
        $delimiter = ",";
        $newline = "\r\n";
        $filename = "files/audits/".$site."_my_question_answer.csv";
        $fileExist = file_exists($filename);
        if($fileExist){
            unlink($filename);
        }

        $my_question_answer = $this->db2
            ->select('
                    nr_audits.audit_id AS AuditId,
                    (DATE_FORMAT(FROM_UNIXTIME(nr_audits.created_date), "'.$format.'"))  AS CreatedDate,
                    APD.policy_number AS PolicyNumber, 
                    (DATE_FORMAT(FROM_UNIXTIME(nr_audit_schedules.schedule_date), "'.$format.'"))  AS ScheduleDate,
                    nr_audit_schedules.schedule_from_time AS FromTime,
                    nr_audit_schedules.schedule_to_time AS ToTime, 
                    (DATE_FORMAT(FROM_UNIXTIME(APD.policy_period_from), "'.$format.'"))  AS PolicyStartDate,
                    (DATE_FORMAT(FROM_UNIXTIME(APD.policy_period_to), "'.$format.'"))  AS PolicyEndDate,
                    AID.insured_name AS InsuredName,
                    nr_contacts.contact AS phone_number,
                    cm.carrier_name AS CarrierName,
                    nr_audit_type.audit_type_text AS AuditType,
                    nr_address.r_country AS Country,
                    nr_address.r_city AS City,
                    nr_address.r_zipcode AS ZipCode,
                    nr_address.r_state AS State,
                    nr_address.r_address AS Address,
                    nr_audit_status.status_text AS Status,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS AuditorName,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS ReviewerName,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as Tags,
                    nr_audits.source AS Source
                ')        
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_statement_details','nr_audits.audit_id=nr_carrier_statement_details.audit_id','left')
        ->join('nr_states','nr_address.r_state = nr_states.state_description OR nr_address.r_state = nr_states.state_name AND (nr_address.related_type=6)','left')
        ->group_by('nr_audits.audit_id');
        $query = $this->db2->get();
        $filter_data = $this->dbutil->csv_from_result($query, $delimiter, $newline);
        $myfile = fopen($filename, "w") or die("Unable to open file!");
        fwrite($myfile,  $filter_data);
        fclose($myfile);
        return $filename;
    }

    /**
     * Description : Returns Audit list in the form which datatable expects
     * Note : 
    */
    public function getMyReviewQuestionsAudits($postdata){
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }

        if($where!=''){
            $where .= "AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(2);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(2);
            $this->db2->where($where);
        }
        if($length!=-1){
            $this->db2->limit($length,$start);
        }
        return $this->db2
            ->select('
                    schedule_id, fed_id,
                    nr_audits.audit_id,nr_audits.created_date,nr_audits.source,nr_audits.is_active,nr_audits.audit_group_id,nr_audits.is_primary_in_group,nr_audits.is_non_coop,nr_audits.status_id,
                    nr_audits.modified_date,
                    APD.policy_number, APD.policy_period_to,
                    APD.created_date as policy_created_date,
                    AID.insured_name,
                    nr_contacts.contact AS phone_number,
                    cm.carrier_name,
                    nr_audit_type.audit_type_text,
                    nr_address.r_lat,nr_address.r_lon,nr_address.r_country,r_county,nr_address.r_city,nr_address.r_zipcode,nr_address.r_state,nr_address.r_address,
                    nr_audit_status.status_text,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    nr_audits_xml_details.control_id,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->order_by($column_name,$order)
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
     * Description : Returns Filtered Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getMyReviewQuestionsAuditsFilterTot($postdata){
        $where ='';
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $user_id= $this->currentUser['user_id'];
        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $tagSearch = '';
        if ($search) {
            $search = str_replace("+", ',', $search,$count);
            if(!$count){
                $where .= "
                    (
                    nr_audits.audit_id like '%".$search."%' 
                    OR nr_audits.modified_date like '%".$search."%'
                    OR policy_number like '%".$search."%'
                    OR source like '%".$search."%'
                    OR insured_name like '%".$search."%'
                    OR carrier_name like '%".$search."%'
                    OR audit_type_text like '%".$search."%'
                    OR nr_tags.tag_name like '%".$search."%'
                    OR nr_users.first_name like '%".$search."%'
                    OR nr_users.last_name like '%".$search."%'
                    OR nr_usersR.first_name like '%".$search."%'
                    OR nr_usersR.last_name like '%".$search."%'
                    OR r_address like '%".$search."%'
                    OR r_city like '%".$search."%'
                    OR r_country like '%".$search."%'
                    OR r_county like '%".$search."%'
                    OR r_state like '%".$search."%'
                    OR nr_contacts.contact like '%".$search."%'
                    OR nr_audits_xml_details.control_id like '%".$search."%'
                    OR nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code like '%".$search."%')
                    )
                ";
            }else{
                if(strrpos($search, ',')+1 == strlen($search)){
                    $search[strrpos($search, ',')] = ' ';
                    $search = trim($search);
                }
                $search = '"'.str_replace(",", '","', $search).'"';
                $where .= "
                    (
                        nr_audits.audit_id in (select nr_audits_class_codes.audit_id from nr_classes right join nr_audits_class_codes on nr_audits_class_codes.class_id=nr_classes.class_id where class_code in (".$search.") GROUP by audit_id )
                    )
                ";
            }
        }
        if($where!=''){
            $where .= "AND (nr_audits.is_active = 1 AND nr_audits.is_matured = 1)";
            $where .= $this->getQuestionWhereGroupBy(2);
            $this->db2->where($where);
        }else{
            $where .= "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
            $where .= $this->getQuestionWhereGroupBy(2);
            $this->db2->where($where);
        }
        $result = $this->db2
        ->select('
                    count(nr_audits.audit_id) as tot,
                ')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as cm','cm.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_audit_type','nr_audit_type.audit_type_id=nr_audits.audittype_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary=1','left')
        ->join('nr_contacts','nr_contacts.related_id=nr_audits.audit_id and nr_contacts.related_type = 4 AND nr_contacts.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
        ->join('nr_audits_xml_details','nr_audits_xml_details.audit_id=nr_audits.audit_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
        return $result;
    }

    /**
     * Description : Returns Audit list Total in the form which datatable expects
     * Note : 
    */
    public function getMyReviewQuestionsAuditsTot($postdata){
        $where = "nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']==8){
            $where .= " AND nr_carrier_user_links.user_id = ".$this->currentUser['user_id']." ";
        }else if($this->currentUser['role_id']==5){
            $audit_ids = $this->AuditsModel->getAuditsAuditorWise();
            $this->db2->where("nr_audits.audit_id IN (".$audit_ids.")",NULL, false);
        }
        $where .= $this->getQuestionWhereGroupBy(2);
        return $this->db2
        ->where($where)
        ->select('nr_audits.audit_id as tot')
        ->from('nr_audits')
        ->join('nr_carrier_user_links','nr_carrier_user_links.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 and nr_address.related_type AND nr_address.is_primary=1','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
        * Description : get total visited policy for datatable
        * Note : 
    */
    public function getReviewAuditsTot($postdata){

        $where ='';

        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }
        $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        $where .= " AND nr_audits.status_id = 8 ";

        if($this->currentUser['role_id']<=4){
            
        }else{
            $where .= " AND nr_audits.reviewed_by != 0";
        }

        return $this->db2
        ->where($where)
        ->select('count(nr_audit_schedules.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }
    
    /**
        * Description : get visited policy list for datatable
        * Note : 
    */
    public function getReviewAudits($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR nr_usersR.first_name like '%".$search."%'
                OR nr_usersR.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 8 ";

        if($this->currentUser['role_id']<=4){
            
        }else{
            $where .= " AND nr_audits.reviewed_by != 0";
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get visited policy list total as per filters for datatable
        * Note : 
    */
    public function getReviewAuditsfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR nr_usersR.first_name like '%".$search."%'
                OR nr_usersR.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        $where .= " AND nr_audits.status_id = 8 ";

        if($this->currentUser['role_id']<=4){
            
        }else{
            $where .= " AND nr_audits.reviewed_by != 0";
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

    /**
        * Description : get total visited policy for datatable
        * Note : 
    */
    public function getReReviewAuditsTot($postdata){

        $where ='';

        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }
        $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audits.status_id = 9";
        }else if($this->currentUser['role_id']==5 or $this->currentUser['role_id']==6){
            $where .= " AND nr_audits.status_id = 9";
            $where .= " AND nr_audits.reviewed_by = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_audits.rereview_by = ".$this->currentUser['user_id'];
        }

        return $this->db2
        ->where($where)
        ->select('count(nr_audit_schedules.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }
    
    /**
        * Description : get visited policy list for datatable
        * Note : 
    */
    public function getReReviewAudits($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR nr_usersR.first_name like '%".$search."%'
                OR nr_usersR.last_name like '%".$search."%'
                OR nr_usersRR.first_name like '%".$search."%'
                OR nr_usersRR.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audits.status_id = 9";
        }else if($this->currentUser['role_id']==5 or $this->currentUser['role_id']==6){
            $where .= " AND nr_audits.status_id = 9";
            $where .= " AND nr_audits.reviewed_by = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_audits.rereview_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    CONCAT(nr_usersRR.first_name , " " , nr_usersRR.last_name) AS rereviewer,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_users as nr_usersRR','nr_usersRR.user_id=nr_audits.rereview_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get visited policy list total as per filters for datatable
        * Note : 
    */
    public function getReReviewAuditsfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR nr_usersR.first_name like '%".$search."%'
                OR nr_usersR.last_name like '%".$search."%'
                OR nr_usersRR.first_name like '%".$search."%'
                OR nr_usersRR.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audits.status_id = 9";
        }else if($this->currentUser['role_id']==5 or $this->currentUser['role_id']==6){
            $where .= " AND nr_audits.status_id = 9";
            $where .= " AND nr_audits.reviewed_by = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_audits.rereview_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    CONCAT(nr_usersRR.first_name , " " , nr_usersRR.last_name) AS rereviewer,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_users as nr_usersRR','nr_usersRR.user_id=nr_audits.rereview_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get total visited policy for datatable
        * Note : 
    */
    public function getReCheckAuditsTot($postdata){

        $where ='';

        if(isset($postdata['audit_id']) AND $postdata['audit_id']!=""){
            $where .= "nr_audits.audit_id IN (".$postdata['audit_id'].")";
        }else if(sizeof($postdata['filter_data'])){
            $where .= "1 "; 
        }else{
            $where .= "1 "; 
        }
        $where .= " AND nr_audits.is_active = 1 AND nr_audits.is_matured = 1";

        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audits.status_id = 10";
        }else if($this->currentUser['role_id']==5){
            $where .= " AND nr_audits.status_id = 10 AND nr_audit_schedules.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_audits.recheck_by = ".$this->currentUser['user_id'];
        }

        return $this->db2
        ->where($where)
        ->select('count(nr_audit_schedules.audit_id) as tot')
        ->from('nr_audits')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->get()
        ->result_array(); 
    }
    
    /**
        * Description : get visited policy list for datatable
        * Note : 
    */
    public function getReCheckAudits($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR nr_usersR.first_name like '%".$search."%'
                OR nr_usersR.last_name like '%".$search."%'
                OR nr_usersRR.first_name like '%".$search."%'
                OR nr_usersRR.last_name like '%".$search."%'
                OR nr_usersRC.first_name like '%".$search."%'
                OR nr_usersRC.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audits.status_id = 10";
        }else if($this->currentUser['role_id']==5){
            $where .= " AND nr_audits.status_id = 10 AND nr_audit_schedules.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_audits.recheck_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_tentative.tentative_id, nr_tentative.schedule_date as tentative_schedule_date,start_date,end_date,days,from_time,to_time,
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id, is_reschedule, nr_audit_schedules.reason,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    CONCAT(nr_usersRR.first_name , " " , nr_usersRR.last_name) AS rereviewer,
                    CONCAT(nr_usersRC.first_name , " " , nr_usersRC.last_name) AS recheck,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
       
        ->order_by($column_name,$order)
        ->limit($length,$start)
        ->from('nr_audits')
        ->join('nr_tentative','nr_tentative.audit_id=nr_audits.audit_id','left')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_users as nr_usersRR','nr_usersRR.user_id=nr_audits.rereview_by','left')
        ->join('nr_users as nr_usersRC','nr_usersRC.user_id=nr_audits.recheck_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }
    
    /**
        * Description : get visited policy list total as per filters for datatable
        * Note : 
    */
    public function getReCheckAuditsfilterTot($postdata){

        $length= $postdata['length'];
        $start = $postdata['start'];
        $draw= $postdata['draw'];
        $column_name = $postdata['column_name'];
        $order = $postdata['order'];
        $search= $postdata['search'];
        $whereActive='';
        $where ='';

        if ($search) {
            $where .= "
                (
                nr_audits.audit_id like '%".$search."%' 
                OR policy_number like '%".$search."%'
                OR nr_audit_schedules.schedule_date like '%".$search."%'
                OR schedule_from_time like '%".$search."%'
                OR schedule_to_time like '%".$search."%'
                OR nr_users.first_name like '%".$search."%'
                OR nr_users.last_name like '%".$search."%'
                OR nr_usersR.first_name like '%".$search."%'
                OR nr_usersR.last_name like '%".$search."%'
                OR nr_usersRR.first_name like '%".$search."%'
                OR nr_usersRR.last_name like '%".$search."%'
                OR nr_usersRC.first_name like '%".$search."%'
                OR nr_usersRC.last_name like '%".$search."%'
                OR insured_name like '%".$search."%'
                OR carrier_name like '%".$search."%'
                OR policy_period_to like '%".$search."%'
                OR r_address like '%".$search."%'
                OR r_zipcode like '%".$search."%'
                OR nr_tags.tag_name like '%".$search."%'
                )
            ";
        }
        $flag = 0;

        if(sizeof($postdata['filter_data']) AND isset($postdata['filter_data']['schedule_date']) AND $postdata['filter_data']['schedule_date']==""){
            unset($postdata['filter_data']['schedule_date']);
        }

        if(sizeof($postdata['filter_data']) ){
            if($where!=""){
                $where .= " AND ";
            }

            $where .= "(";
                if(isset($postdata['filter_data']['schedule_date'])){

                    $schedule_date = datetotime($postdata['filter_data']['schedule_date'],$this->companySetting->dateformat);

                    $where .= " nr_audit_schedules.schedule_date =".$schedule_date;

                    $flag = 1;
                }
                
                if(isset($postdata['filter_data']['user_id'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['user_id2'])){
                    if($flag==1){
                        $where .= " AND nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                    }else{
                        $where .= " nr_audit_schedules.user_id =".$postdata['filter_data']['user_id2'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['tag_id'])){
                    if($flag==1){
                        $where .= " AND nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                    }else{
                        $where .= " nr_audits_tags.tag_id = ".$postdata['filter_data']['tag_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['carrier_id'])){
                    if($flag==1){
                        $where .= " AND CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                    }else{
                        $where .= "CM.carrier_id =".$postdata['filter_data']['carrier_id'];
                        $flag=1;
                    }
                }

                if(isset($postdata['filter_data']['zipcode'])){
                    if($flag==1){
                        $where .= " AND nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                    }else{
                        $where .= "nr_address.r_zipcode like '%".$postdata['filter_data']['zipcode']."%'";
                        $flag=1;
                    }
                }
            $where .= ")";
        }
        if($where!=''){
            $where .= " AND ";
        }
        $where .= " (nr_audits.is_active = 1)  AND (nr_audits.is_matured = 1)";
        if($this->currentUser['role_id']<=4){
            $where .= " AND nr_audits.status_id = 10";
        }else if($this->currentUser['role_id']==5){
            $where .= " AND nr_audits.status_id = 10 AND nr_audit_schedules.user_id = ".$this->currentUser['user_id'];
        }else{
            $where .= " AND nr_audits.recheck_by = ".$this->currentUser['user_id'];
        }
        $this->db2->where($where);

        return $this->db2
        ->select('
                    nr_audits.audit_id,
                    CSR.reminder_at as reminder_at_c,
                    SSR.reminder_at as reminder_at_s,
                    APD.policy_number, APD.policy_period_to,APD.created_date as policy_created_date,
                    nr_audit_schedules.schedule_date, schedule_from_time, schedule_to_time, schedule_id,
                    nr_audit_schedules.user_id,
                    nr_audit_schedules.user_id as id,
                    CONCAT(nr_users.first_name , " " , nr_users.last_name) AS auditor,
                    CONCAT(nr_usersR.first_name , " " , nr_usersR.last_name) AS reviewer,
                    CONCAT(nr_usersRR.first_name , " " , nr_usersRR.last_name) AS rereviewer,
                    CONCAT(nr_usersRC.first_name , " " , nr_usersRC.last_name) AS recheck,
                    AID.insured_name,
                    CM.carrier_name,
                    r_address,r_lat,r_lon,r_zipcode,
                    GROUP_CONCAT( DISTINCT nr_tags.tag_name) as tags
                ')
        ->from('nr_audits')
        ->join('nr_schedule_reminders as CSR','CSR.audit_id=nr_audits.audit_id AND CSR.reminder_for = 0','left')
        ->join('nr_schedule_reminders as SSR','SSR.audit_id=nr_audits.audit_id AND SSR.reminder_for = 1','left')
        ->join('nr_audits_policy_details as APD','APD.audit_id=nr_audits.audit_id','left')
        ->join('nr_audit_schedules','nr_audit_schedules.audit_id=nr_audits.audit_id','left')
        ->join('nr_users','nr_users.user_id=nr_audit_schedules.user_id','left')
        ->join('nr_users as nr_usersR','nr_usersR.user_id=nr_audits.reviewed_by','left')
        ->join('nr_users as nr_usersRR','nr_usersRR.user_id=nr_audits.rereview_by','left')
        ->join('nr_users as nr_usersRC','nr_usersRC.user_id=nr_audits.recheck_by','left')
        ->join('nr_audits_insured_details as AID','AID.audit_id=nr_audits.audit_id','left')
        ->join('nr_carrier_master as CM','CM.carrier_id=nr_audits.carrier_id','left')
        ->join('nr_address','nr_address.related_id=nr_audits.audit_id and nr_address.related_type = 6 AND nr_address.is_primary = 1','left')
        ->join('nr_audits_tags','nr_audits_tags.audit_id=nr_audits.audit_id','left')
        ->join('nr_tags','nr_tags.tag_id=nr_audits_tags.tag_id','left')
        ->group_by('nr_audits.audit_id')
        ->get()
        ->result_array();
    }

     /**
        * Description : audit 
        * Note : 
    */
    public function new_status_allow_audit_status(){
        return $this->db2
            ->select('status_ids')
            ->where('type','new_status_allow_in_status_id1')
            ->get('nr_notify_settings')
            ->row_array();
    }

    public function reviewerAssignMultipleAudits($data){
        if($data['checked_ids'] != null && $data['checked_ids'] != ""){
            $result=array_diff($data['checked_ids'],$data['audit_ids']);
        }else{
            $result=$data['audit_ids'];
        }
        if(isset($this->currentTimestamp)){ $timestamp = $this->currentTimestamp; } else { $timestamp = gettimestamp(); }
        $user_id = $this->currentUser['user_id'];
        $where = "nr_audits.audit_id IN (".implode(',', $result).")";
        $result_data = $this->db2->select('nr_audits.audit_id,nr_audits_policy_details.policy_number')
                           ->from('nr_audits')
                           ->join('nr_audits_policy_details','nr_audits.audit_id=nr_audits_policy_details.audit_id','left')
                           ->where($where)
                           ->get()
                           ->result_array(); 
        foreach ($result as $key => $value) {
            //get old user by assing audit in log history
            $getData = $this->db2->select('*')->from('nr_activity_logs')->where('action_column','pick_by')->where('action_table','nr_audits')->where('action','changed')->where('extras',$value)->order_by('activity_log_id','desc')->get()->row_array();
            if(empty($getData)){
                $old_users_id = '0';
            }else{
                $old_users_id = $getData['action_value'];
            }
            if(isset($data['page']) AND $data['page'] == 'all_audits'){
                $status_id = $this->MastersModel->getTableWiseColumnValue('nr_audits', 'status_id', array('audit_id' => $value));
                $open_pool_status_id = $this->myReviewPoolAllowStatusIds();
                if(in_array($status_id, $open_pool_status_id)){
                    $update_data['modified_by'] = $user_id;
                    $update_data['modified_date'] = $timestamp;
                    $update_data['modified_host'] = NRM_HOST_ADDRESS;
                    if($data['assign'] == 1){
                        $update_data['pick_by'] = $data['reviewer_id'];
                        $this->db2
                            ->where('audit_id',$value)
                            ->update('nr_audits',$update_data);
                        
                        $dates['created_date'] = gettimestamp();
                        $dates['created_host'] = NRM_HOST_ADDRESS;
                        $history['action'] = 'changed';
                        $history['action_column'] = 'pick_by';
                        $history['action_table'] = 'nr_audits';
                        $history['action_value'] = $data['reviewer_id'];
                        $history['action_value_old'] = $old_users_id;
                        $history['action_extras'] = $value;
                        $history['extras'] = $value;
                        $history['related_type'] = 1;
                        $history['action_by'] = $this->currentUser['user_id'];
                        $history['action_date'] = $dates['created_date'];
                        $history['action_host'] = $dates['created_host'];
                        $this->db2->insert('nr_activity_logs',$history);
                    }else{
                        $update_data['pick_by'] = 0;
                        $this->db2
                            ->where('audit_id',$value)
                            ->update('nr_audits',$update_data);
                        
                        $get_audit_last_status = $this->db2
                            ->select('*')
                            ->from('nr_activity_logs')
                            ->where('action_extras',$value)
                            ->where('action_column','status_id')
                            ->where('action_table','nr_audits')
                            ->order_by('activity_log_id','desc')
                            ->limit(1)
                            ->get()
                            ->row_array();

                        $dates['created_date'] = gettimestamp();
                        $dates['created_host'] = NRM_HOST_ADDRESS;
                        $history['action'] = 'changed';
                        $history['action_column'] = 'status_id';
                        $history['action_table'] = 'nr_audits';
                        $history['action_value'] = $get_audit_last_status['action_value_old'];
                        $history['action_value_old'] = $get_audit_last_status['action_value'];
                        $history['action_extras'] = $value;
                        $history['extras'] = $value;
                        $history['related_type'] = 1;
                        $history['action_by'] = $this->currentUser['user_id'];
                        $history['action_date'] = $dates['created_date'];
                        $history['action_host'] = $dates['created_host'];
                        $this->db2->insert('nr_activity_logs',$history);
                    }
                    $nr_audit_status['status_id'] = 41;
                    $nr_audit_status['audit_id'] = $value;
                    $this->AuditsModel->changeAuditStatusTo($nr_audit_status);
                }else{
                    return $return_array = array(
                        'data' => $data['audit_ids'],
                        'result' => $result_data,
                        'status' => 1
                    );                     
                }                
            }else{
                $update_data['modified_by'] = $user_id;
                $update_data['modified_date'] = $timestamp;
                $update_data['modified_host'] = NRM_HOST_ADDRESS;
                if($data['assign'] == 1){
                    $update_data['pick_by'] = $data['reviewer_id'];
                    $this->db2
                        ->where('audit_id',$value)
                        ->update('nr_audits',$update_data);
                    $nr_audit_status['status_id'] = 41;
                    $nr_audit_status['audit_id'] = $value;
                    $this->AuditsModel->changeAuditStatusTo($nr_audit_status);
                    if($this->currentUser['role_id'] == 1 || $this->currentUser['role_id'] == 2 || $this->currentUser['role_id'] == 7){
                        $dates['created_date'] = gettimestamp();
                        $dates['created_host'] = NRM_HOST_ADDRESS;
                        $history['action'] = 'changed';
                        $history['action_column'] = 'pick_by';
                        $history['action_table'] = 'nr_audits';
                        $history['action_value'] = $data['reviewer_id'];
                        $history['action_value_old'] = $old_users_id;
                        $history['action_extras'] = $value;
                        $history['extras'] = $value;
                        $history['related_type'] = 1;
                        $history['action_by'] = $this->currentUser['user_id'];
                        $history['action_date'] = $dates['created_date'];
                        $history['action_host'] = $dates['created_host'];
                        $this->db2->insert('nr_activity_logs',$history);
                    }else if($this->currentUser['role_id'] == 6){
                        $dates['created_date'] = gettimestamp();
                        $dates['created_host'] = NRM_HOST_ADDRESS;
                        $history['action'] = 'changed';
                        $history['action_column'] = 'pick_by';
                        $history['action_table'] = 'nr_audits';
                        $history['action_value'] = $this->currentUser['user_id'];
                        $history['action_value_old'] = $old_users_id;
                        $history['action_extras'] = $value;
                        $history['extras'] = $value;
                        $history['related_type'] = 1;
                        $history['action_by'] = $this->currentUser['user_id'];
                        $history['action_date'] = $dates['created_date'];
                        $history['action_host'] = $dates['created_host'];
                        $this->db2->insert('nr_activity_logs',$history);
                    }
                }else{
                    $get_audit_last_status = $this->db2
                        ->select('*')
                        ->from('nr_activity_logs')
                        ->where('action_extras',$value)
                        ->where('action_column','status_id')
                        ->where('action_table','nr_audits')
                        ->order_by('activity_log_id','desc')
                        ->limit(1)
                        ->get()
                        ->row_array();

                    $dates['created_date'] = gettimestamp();
                    $dates['created_host'] = NRM_HOST_ADDRESS;
                    $history['action'] = 'changed';
                    $history['action_column'] = 'status_id';
                    $history['action_table'] = 'nr_audits';
                    $history['action_value'] = $get_audit_last_status['action_value_old'];
                    $history['action_value_old'] = $get_audit_last_status['action_value'];
                    $history['action_extras'] = $value;
                    $history['extras'] = $value;
                    $history['related_type'] = 1;
                    $history['action_by'] = $this->currentUser['user_id'];
                    $history['action_date'] = $dates['created_date'];
                    $history['action_host'] = $dates['created_host'];
                    $this->db2->insert('nr_activity_logs',$history);

                    $update_data['pick_by'] = 0;
                    $update_data['status_id'] = $get_audit_last_status['action_value_old'];
                    $this->db2
                        ->where('audit_id',$value)
                        ->update('nr_audits',$update_data);
                        
                    if($data['reviewer_id'] == $old_users_id){
                        $action_old_value_ = $data['reviewer_id'];
                        $action_value_ = 0;
                    }else{
                        $action_old_value_ = $old_users_id;
                        $action_value_ = 0;
                    }
                    if($this->currentUser['role_id'] == 1 || $this->currentUser['role_id'] == 2 || $this->currentUser['role_id'] == 3 || $this->currentUser['role_id'] == 4 || $this->currentUser['role_id'] == 7 || $this->currentUser['role_id'] == 13){
                        $dates['created_date'] = gettimestamp();
                        $dates['created_host'] = NRM_HOST_ADDRESS;
                        $history['action'] = 'changed';
                        $history['action_column'] = 'pick_by';
                        $history['action_table'] = 'nr_audits';
                        $history['action_value'] = $action_value_;
                        $history['action_value_old'] = $action_old_value_;
                        $history['action_extras'] = $value;
                        $history['extras'] = $value;
                        $history['related_type'] = 1;
                        $history['action_by'] = $this->currentUser['user_id'];
                        $history['action_date'] = $dates['created_date'];
                        $history['action_host'] = $dates['created_host'];
                        $this->db2->insert('nr_activity_logs',$history);
                    }else if($this->currentUser['role_id'] == 6){
                        $dates['created_date'] = gettimestamp();
                        $dates['created_host'] = NRM_HOST_ADDRESS;
                        $history['action'] = 'changed';
                        $history['action_column'] = 'pick_by';
                        $history['action_table'] = 'nr_audits';
                        $history['action_value'] = $action_value_;
                        $history['action_value_old'] = $action_old_value_;
                        $history['action_extras'] = $value;
                        $history['extras'] = $value;
                        $history['related_type'] = 1;
                        $history['action_by'] = $this->currentUser['user_id'];
                        $history['action_date'] = $dates['created_date'];
                        $history['action_host'] = $dates['created_host'];
                        $this->db2->insert('nr_activity_logs',$history);
                    }
                }
            }
        }
        return true;
    }

    /**
        * Description : Allow status in my review pool
        * Note : 
    */
    public function myReviewPoolAllowStatusIds(){
        $status_ids = $this->MastersModel->getTableWiseColumnValue(
                    'nr_notify_settings',
                    'status_ids',
                    array('type' => 'my_review_pool_audit_status_ids')
                );
        return explode(',', $status_ids);
    }

    /**
        * Description : Allow status in my review pool
        * Note : 
    */
    public function nonCoopWithComplateAudit($search,$filter_data){
        $flag=0;
        $where='';
        if($search!=""){
            $where .= "( nr_audits_policy_details.policy_number like '%".$search."%' )";
            $this->db2->where($where);
            $flag=1;
        }
        if($filter_data!=null){
            if(isset($filter_data['user_id'])){
                if($flag==1){
                    $where .= " AND ( nr_audits_non_coop_notes.created_by='".$filter_data['user_id']."' )";
                }else{
                    $where .= "( nr_audits_non_coop_notes.created_by='".$filter_data['user_id']."' )";
                    $flag=1;
                }
            }
            if(isset($filter_data['carrier_id'])){
                if($flag==1){
                    $where .= " AND ( nr_audits.carrier_id='".$filter_data['carrier_id']."' )";
                }else{
                    $where .= " ( nr_audits.carrier_id='".$filter_data['carrier_id']."' )";
                }
            }
            $this->db2->where($where);
        }
        $nonCoopids = $this->db2
                            ->select('nr_audits_non_coop_notes.notes_complete_by_auditor,nr_audits_non_coop_notes.audit_id')
                            ->from('nr_audits_non_coop_notes')
                            ->join('nr_audits','nr_audits.audit_id=nr_audits_non_coop_notes.audit_id','left')
                            ->join('nr_audit_status','nr_audit_status.status_id=nr_audits.status_id','left')
                            ->join('nr_audits_policy_details','nr_audits_non_coop_notes.audit_id=nr_audits_policy_details.audit_id','left')
                            ->where('nr_audits_non_coop_notes.notes_complete_by_auditor',1)
                            ->where('nr_audits.status_id',30)
                            ->group_by('nr_audits_non_coop_notes.audit_id')
                            ->get()
                            ->result_array();
        $nonCoopIdsArray=array();          
        if(count($nonCoopids) > 0){
            foreach ($nonCoopids as $key => $value) {
                array_push($nonCoopIdsArray, $value['audit_id']);
            }
        }
        return $nonCoopIdsArray;
    }

    /**
        * Description : getting audit ids (invoice generated or carrier statment generated)
        * Note : 
    */
    public function getInvoiceStatmentGeneratedIds(){
        $this->db2->select('GROUP_CONCAT(audit_id) AS ids');
        $invoice_details = $this->db2->get('nr_auditor_invoice_details')->result_array();
         
        $this->db2->select('GROUP_CONCAT(audit_id) AS ids');
        $statement_details = $this->db2->get('nr_carrier_statement_details')->result_array();
        $final_ids = array_merge(explode(',', $statement_details[0]['ids']),explode(',', $invoice_details[0]['ids']));
        return implode($final_ids, ',');
    }

    /**
        * Description : getting Carrier billed/paid auits Ids
        * Note : paramerter 1 for paid and 2 all autdit billed
    */
    public function getCarrierBilledPaidAuidtsIds($params){
        $where = "";
        if($params == 1){
            $where = "nr_carrier_statements.statement_status = 1";
            $this->db2->where($where);
        }
        $data = $this->db2->select('audit_id')
                    ->from('nr_carrier_statement_details')
                    ->join('nr_carrier_statements','nr_carrier_statements.statement_id=nr_carrier_statement_details.statement_id')
                    ->get()
                    ->result_array();
        return implode(',', array_column($data, 'audit_id'));
    }

    public function policyBackToOpenPool($postdata){
        foreach ($postdata as $key => $value) {
            $where = "action_table='nr_audits' AND action_column='status_id' AND action='changed' AND action_extras='".$value."'";
            //update nr audit data
            $getData = $this->db2
                    ->select('*')
                    ->where($where)
                    ->order_by('activity_log_id','desc')
                    ->get('nr_activity_logs')
                    ->row_array();
            $update_data['status_id'] = $getData['action_value_old'];
            $update_data['pick_by'] = 0;
            $this->db2
                ->where('audit_id',$value)
                ->update('nr_audits',$update_data);
            //update activity log data
            $dates['created_date'] = gettimestamp();
            $dates['created_host'] = NRM_HOST_ADDRESS;
            $history['action'] = 'changed';
            $history['action_column'] = 'status_id';
            $history['action_table'] = 'nr_audits';
            $history['action_value'] = $getData['action_value_old'];
            $history['action_value_old'] = $getData['action_value'];
            $history['action_extras'] = $value;
            $history['extras'] = $value;
            $history['related_type'] = 1;
            $history['action_by'] = $this->currentUser['user_id'];
            $history['action_date'] = $dates['created_date'];
            $history['action_host'] = $dates['created_host'];
            $this->db2->insert('nr_activity_logs',$history);
            }
        return true;
    }

    public function auditAssignToSecretary($postdata){
        if(isset($this->currentTimestamp)){
            $timestamp = $this->currentTimestamp;
        }else{
            $timestamp = gettimestamp();
        }
        foreach ($postdata['audit_ids'] as $key => $value) {
            $update_data['modified_by'] = $this->currentUser['user_id'];
            $update_data['modified_date'] = $timestamp;
            $update_data['modified_host'] = NRM_HOST_ADDRESS;
            $update_data['audit_assign_to_secretary'] = $postdata['secretary_id'];
            $this->db2
                ->where('audit_id',$value)
                ->update('nr_audits',$update_data);
            
            $getAuditPreviousStatus = $this->db2
                    ->from('nr_activity_logs')
                    ->where('extras',$value)
                    ->where('action_table','nr_audits')
                    ->where('action_column','audit_assign_to_secretary')
                    ->order_by('activity_log_id','DESC')
                    ->limit(1)
                    ->get()
                    ->row_array();

                $old_value=0;
                if($getAuditPreviousStatus!="" AND $getAuditPreviousStatus!=NULL){
                    $old_value=$getAuditPreviousStatus['action_value'];
                }                

            $dates['created_date'] = gettimestamp();
            $dates['created_host'] = NRM_HOST_ADDRESS;
            $history['action'] = 'changed';
            $history['action_column'] = 'audit_assign_to_secretary';
            $history['action_table'] = 'nr_audits';
            $history['action_value'] = $postdata['secretary_id'];
            $history['action_value_old'] = $old_value;
            $history['action_extras'] = $value;
            $history['extras'] = $value;
            $history['related_type'] = 1;
            $history['action_by'] = $this->currentUser['user_id'];
            $history['action_date'] = $dates['created_date'];
            $history['action_host'] = $dates['created_host'];
            $this->db2->insert('nr_activity_logs',$history);
        }
        return true;
    }
}
