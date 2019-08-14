<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */

//osc block class for call model class file according to action.
class Oscprofessionals_Magemobapp_Model_Osc extends Mage_Core_Model_Abstract
{
    
    /**
     * @var Oscprofessionals_Magemobapp_Helper_Data
     */
    protected $_helper;
    
    public function __construct()
    {
        $this->_helper = Mage::helper('magemobapp');
    }
    
    /**
     * Run processRequest from controller
     * @param array $data
     * @return mixed
     */
    public function processRequest($data)
    {
        
        if (isset($data['action'])) {
            try {
                $result['message'] = '';
                $result['error']   = 0;
                $result['data']    = $this->processRequestRun($data);
            }
            catch (Exception $e) {
                $result['error']   = 1;
                $result['message'] = $e->getMessage();
            }
        }
        
        return $result;
        
    }
    
    /**
     * Run processRequestRun
     * @param array $data
     * @return mixed
     */
    public function processRequestRun($data)
    {
        if (empty($data['action'])) {
            throw new Exception($this->_helper->__('No method is specified'));
        }
        
        // Check param input
        if (!empty($data['params'])) {
            $params = $data['params'];
        }
        
        $actionName = $data['action'];
        
        if ($actionName == 'login') {
            
            if (empty($params['username']) || empty($params['password'])) {
                
                throw new Exception($this->_helper->__('Miss username or password to login'));
                
            } else {
                
                try {
                    return Mage::getModel('magemobapp/login')->login($params['username'], $params['password']);
                }
                catch (Exception $e) {
                    throw new Exception($this->_helper->__('Failed to login'));
                }
            }
        }else if($actionName == 'logoff'){

                try {
                    return Mage::getModel('magemobapp/logoff')->logoff();
                }
                catch (Exception $e) {
                    throw new Exception($this->_helper->__('Failed to logoff'));
                }

        }

        
        if (Mage::getModel('magemobapp/login')->isCheckLogin()) {
            
            if (!Mage::getModel('magemobapp/login')->isCheckRole()) { // if not role
                
                throw new Exception($this->_helper->__('Access Denied.'));
            }
            
            
            if (empty($actionName)) {
                throw new Exception($this->_helper->__('Invalid method.'));
            }
            
            // swich action for find class file and method
            switch ($actionName) {
                
                case "dashboard":
                    //call dashboard class
                    $className  = 'dashboard';
                    $methodName = 'oscIndex';
                    break;
                
                case "sitehealth":
                    //call sitehealth class
                    $className  = 'sitehealth';
                    $methodName = 'oscIndex';
                    break;
                case "notification":
                    //call notification class
                    $className  = 'notification';
                    $methodName = 'oscIndex';
                    break;
                
                case "orderslist":
                    //call order class
                    $className  = 'orders';
                    $methodName = 'getOrderList';
                    break;
                
                case "orderinfo":
                    $className  = 'orders';
                    $methodName = 'oscIndex';
                    break;
                
                case "todaysorderlist":
                    //call order class
                    $className  = 'orders';
                    $methodName = 'getTodaysOrderList';
                    break;
                
                case "customerslist":
                    //call customers class
                    $className  = 'customers';
                    $methodName = 'getCustomerList';
                    break;
                
                case "customerinfo":
                    //call customers class
                    $className  = 'customers';
                    $methodName = 'oscIndex';
                    break;
                
                case "stockalert":
                    //call stock class
                    $className  = 'stock';
                    $methodName = 'oscIndex';
                    break;
                
            
                case "abandonedlist":
                    //call customers class
                    $className  = 'customers';
                    $methodName = 'getAbandonedCustomersList';
                    break;
                    
            }
            
            
            $model = Mage::getModel('magemobapp/' . $className);
            
            if (!$model) {
                throw new Exception($this->_helper->__('Method not exists.'));
            }
            
            if (is_callable(array(
                &$model,
                $methodName
            ))) {
                return call_user_func_array(array(
                    &$model,
                    $methodName
                ), array(
                    $params
                )); //($param1 = array())
            }
            throw new Exception($this->_helper->__('Resource cannot callable.'));
        }
        throw new Exception($this->_helper->__('Not login.'));
    }
    
}