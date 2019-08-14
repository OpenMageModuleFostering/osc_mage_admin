<?php
/**
 *
 * @category   Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */

class Oscprofessionals_Magemobapp_IndexController extends Mage_Core_Controller_Front_Action
{
    
    public function indexAction()
    {
        
        //set header
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        
        //getStoreId($_POST);
        $data = array();
        
        //Store action from Post Data
        $data['action'] = $this->getRequest()->getPost('action');
        
        //Store Params from Post Data
        $data['params'] = $this->getRequest()->getPost('params');
        
        $oscModel = Mage::getModel('magemobapp/osc');
        
        try {
            $result = $oscModel->processRequest($data);
        }
        catch (Exception $e) {
			
            $result['message'] = $e->getMessage();
			Mage::log($result['message'] , null, 'oscMageAdim.log');
        }
        
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        
        return $this;
        
    }
}