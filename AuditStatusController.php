<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/REST_Middel_Controller.php';

class AuditStatusController extends REST_Middel_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('AuditStatusModel');
    }

    public function dispute_audit_note_post() {
        $postData = $this->post();
        if ($this->AuditStatusModel->disputeAuditNote($postData)) {
            $response['error'] = false;
            $response['message'] = array("code"=>'723');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function cancel_audit_note_post() {
        $postData = $this->post();
        if ($this->AuditStatusModel->cancelAuditNote($postData)) {
            $response['error'] = false;
            $response['message'] = array("code"=>'695');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function archived_audit_note_post() {
        $postData = $this->post();
        if ($this->AuditStatusModel->archivedAuditNote($postData)) {
            $response['error'] = false;
            $response['message'] = array("code"=>'838');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function getPolicyListForStatus_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getPolicyListForStatusTot($postData);
        $totRecord = sizeof($tot);
        $prdData = $this->AuditStatusModel->getPolicyListForStatus($postData);
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getPolicyListForStatusFilterTot($postData);
            $filterTotal = sizeof($filterTot);
        }else{
            $filterTotal =sizeof($prdData);
        }

        $draw = $postData['draw'];
        
        if(sizeof($postData['filter_data']) || $postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTotal,
                "recordsTotal" => $totRecord
            );
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $totRecord,
                "recordsTotal" => $totRecord
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getCancelPolicyList_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getCancelPolicyListTot($postData);
        $filterTot = $this->AuditStatusModel->getCancelPolicyListfilterTot($postData);
        $filterTot = sizeof($filterTot);
        $prdData = $this->AuditStatusModel->getCancelPolicyList($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getDisputePolicyList_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getDisputePolicyListTot($postData);
        $prdData = $this->AuditStatusModel->getDisputePolicyList($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getDisputePolicyListfilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }
    
    public function getRecentlyVisited_get() {
        $result = $this->AuditStatusModel->getRecentlyVisited();
        $response['data'] = $result;
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function getDisputeAudit_get() {
        $result = $this->AuditStatusModel->getDisputeAudit();
        $response['data'] = $result;
        $this->response($response, REST_Controller::HTTP_OK);
    }
    
    public function getSidebarPolicy_get() {
        $result = $this->AuditStatusModel->getSidebarPolicy();
        $response['data'] = $result;
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function auditPickBy_post(){
        $result = $this->AuditStatusModel->auditPickBy($this->post());
        if ($result) {
            $response['error'] = false;
            $response['data'] = $result;
            $response['message'] = array("code"=>$result);
        }else{  
            $response['error'] = true;
            $response['data'] = $result;
            $response['message'] = array("code"=>'675');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function getSchedulePolicyListAuditorWise_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getSchedulePolicyListAuditorWiseTot($postData);
        $prdData = $this->AuditStatusModel->getSchedulePolicyListAuditorWise($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getSchedulePolicyListAuditorWisefilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }

        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getAuditStatus_get() {
        $getData = $this->get();
        $result = array('data'=>$this->AuditStatusModel->getAuditStatus($getData));
        if ($result) {
            $response['error'] = false;
            $response['data'] = $result;
            $response['message'] = array("code"=> '831');
        }else{  
            $response['error'] = true;
            $response['data'] = $result;
            $response['message'] = array("code"=>'832');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function getAuditStatusHeatMapFilter_get() {
        $getData = $this->get();
        $result = array('data'=>$this->AuditStatusModel->getAuditStatusHeatMapFilter($getData));
        if ($result) {
            $response['error'] = false;
            $response['data'] = $result;
            $response['message'] = array("code"=> '831');
        }else{  
            $response['error'] = true;
            $response['data'] = $result;
            $response['message'] = array("code"=>'832');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function getPoboxLocationPolicyList_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getPoboxLocationPolicyListTot($postData);
        $prdData = $this->AuditStatusModel->getPoboxLocationPolicyList($postData);
        if($postData['search']){
            $filterTot = $this->AuditStatusModel->getPoboxLocationPolicyListfilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getVisitedAuditsAll_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getVisitedAuditsAllTot($postData);
        $filterTot = $this->AuditStatusModel->getVisitedAuditsAllfilterTot($postData);
        $filterTot = sizeof($filterTot);
        $prdData = $this->AuditStatusModel->getVisitedAuditsAll($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getAuditsMyPick_post() {
        return $this->getAllOpenReviewAudits_post(1);
    }

    public function getAllOpenReviewAudits_post($mypick=0){
        $postData = $this->post();
        $postData['mypick'] = $mypick;
        $tot = $this->AuditStatusModel->getAllOpenReviewAuditsTot($postData);
        
        $prdData = $this->AuditStatusModel->getAllOpenReviewAudits($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }

        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getAllOpenReviewAuditsfilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }

        $draw = $postData['draw'];
        if($postData['search'] || sizeof($postData['filter_data'])) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getAllReviewQuestionsAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getAllReviewQuestionsAuditsTot($postData);
        $totRecord = sizeof($tot);
        $prdData = $this->AuditStatusModel->getAllReviewQuestionsAudits($postData);
        $draw = $postData['draw'];
        if($postData['search']){
            $filterTot = $this->AuditStatusModel->getAllReviewQuestionsAuditsFilterTot($postData);
            $filterTotal = sizeof($filterTot);
        }else{
            $filterTotal = sizeof($prdData);
        }
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTotal,
                "recordsTotal" => $totRecord
            );
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $totRecord,
                "recordsTotal" => $totRecord
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }
    public function getAllAnsweredQuestionsAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getAllAnsweredQuestionsAuditsTot($postData);
        $totRecord = sizeof($tot);
        $prdData = $this->AuditStatusModel->getAllAnsweredQuestionsAudits($postData);
        $draw = $postData['draw'];
        if($postData['search']){
            $filterTot = $this->AuditStatusModel->getAllAnsweredQuestionsAuditsFilterTot($postData);
            $filterTotal = sizeof($filterTot);
        }else{
            $filterTotal = sizeof($prdData);
        }
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTotal,
                "recordsTotal" => $totRecord
            );
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $totRecord,
                "recordsTotal" => $totRecord
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getMyAnsweredQuestionsAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getMyAnsweredQuestionsAuditsTot($postData);
        $totRecord = sizeof($tot);
        $prdData = $this->AuditStatusModel->getMyAnsweredQuestionsAudits($postData);
        $draw = $postData['draw'];
        //if($postData['search']) || sizeof($postData['filter_data']){
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getMyAnsweredQuestionsAuditsFilterTot($postData);
            $filterTotal = sizeof($filterTot);
        }else{
            $filterTotal = sizeof($prdData);
        }
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTotal,
                "recordsTotal" => $totRecord
            );
        }else if(sizeof($postData['filter_data'])){
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTotal,
                "recordsTotal" => $totRecord
            );
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $totRecord,
                "recordsTotal" => $totRecord
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getMyAnsweredQuestionsCsv_post(){
        $getData = $this->post();
        $result = $this->AuditStatusModel->getMyAnsweredQuestionsCsv($getData);
        if ($result) {
            $response['error'] = false;
            $response['data'] = $result;
            $response['message'] = array("code"=>'680');
        }else{  
            $response['error'] = true;
            $response['data'] = $result;
            $response['message'] = array("code"=>'681');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function getMyReviewQuestionsAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getMyReviewQuestionsAuditsTot($postData);
        $totRecord = sizeof($tot);
        $prdData = $this->AuditStatusModel->getMyReviewQuestionsAudits($postData);
        $draw = $postData['draw'];
        if($postData['search']){
            $filterTot = $this->AuditStatusModel->getMyReviewQuestionsAuditsFilterTot($postData);
            $filterTotal = sizeof($filterTot);
        }else{
            $filterTotal = sizeof($prdData);
        }
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTotal,
                "recordsTotal" => $totRecord
            );
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $totRecord,
                "recordsTotal" => $totRecord
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getReviewAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getReviewAuditsTot($postData);
        
        $prdData = $this->AuditStatusModel->getReviewAudits($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getReviewAuditsfilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getReReviewAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getReReviewAuditsTot($postData);
        $prdData = $this->AuditStatusModel->getReReviewAudits($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getReReviewAuditsfilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function getReCheckAudits_post(){
        $postData = $this->post();
        $tot = $this->AuditStatusModel->getReCheckAuditsTot($postData);
        $prdData = $this->AuditStatusModel->getReCheckAudits($postData);
        if(isset($prdData['records']) && $prdData['records']==0){
            $tot[0]['tot'] = 0;
        }
        if(sizeof($postData['filter_data']) || $postData['search']){
            $filterTot = $this->AuditStatusModel->getReCheckAuditsfilterTot($postData);
            $filterTot = sizeof($filterTot);
        }else{
            $filterTot = sizeof($prdData);
        }
        $draw = $postData['draw'];
        if($postData['search']) {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']
            );
        }else if(sizeof($postData['filter_data'])){ 
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $filterTot,
                "recordsTotal" => $tot[0]['tot']);
        }else {
            $array = array(
                "data" => $prdData,
                "draw" => $draw,
                "recordsFiltered" => $tot[0]['tot'],
                "recordsTotal" => $tot[0]['tot']
            );
        }
        $this->response($array, REST_Controller::HTTP_OK);
    }

    public function reviewerAssignMultipleAudits_post(){
        $postData = $this->post();
        $result = array('data'=>$this->AuditStatusModel->reviewerAssignMultipleAudits($postData));
        if($postData['assign'] == 1){
            if ($result) {
                $response['error'] = false;
                $response['data'] = $result;
                $response['message'] = array("code"=> '878');
            }else{  
                $response['error'] = true;
                $response['data'] = $result;
                $response['message'] = array("code"=>'879');
            }
        }else{
            if ($result) {
                $response['error'] = false;
                $response['data'] = $result;
                $response['message'] = array("code"=> '881');
            }else{  
                $response['error'] = true;
                $response['data'] = $result;
                $response['message'] = array("code"=>'882');
            }
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function policyBackToOpenPool_post(){
        $postData = $this->post();
        $result = $this->AuditStatusModel->policyBackToOpenPool($postData);
        if ($result) {
            $response['error'] = false;
            $response['data'] = $result;
            $response['message'] = array("code"=>'984');
        }else{  
            $response['error'] = true;
            $response['data'] = $result;
            $response['message'] = array("code"=>'985');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function auditAssignToSecretary_post(){
        $postData = $this->post();
        $result = $this->AuditStatusModel->auditAssignToSecretary($postData);
        if ($result) {
            $response['error'] = false;
            $response['data'] = $result;
            $response['message'] = array("code"=>'1029');
        }else{
            $response['error'] = true;
            $response['data'] = $result;
            $response['message'] = array("code"=>'1030');
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }
}
