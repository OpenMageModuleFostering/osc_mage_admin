<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Logoff
{
    
    public function __construct()
    {
        $this->_helper = Mage::helper('magemobapp');
    }

    /**
     * logoff process
     */
    public function logoff()
    {
        //set time before logout
        if(Mage::getSingleton('admin/session', array('name' => 'adminhtml'))->isLoggedIn()){
            $this->saveLogout();
        }
        //clear all datas
        Mage::getSingleton('admin/session', array('name' => 'adminhtml'))
            ->getCookie()->delete(
                Mage::getSingleton('admin/session', array('name' => 'adminhtml'))
                    ->getSessionName());
        Mage::getSingleton('admin/session', array('name' => 'adminhtml'))->unsetAll();
        Mage::getSingleton('adminhtml/session')->unsetAll();

    }

    /**
     * save config for earch admin time logged out
     */
    public function saveLogout(){
        $username = Mage::getSingleton('admin/session')->getUser()->getUsername();
        $date = Mage::getModel('core/date')->gmtDate(); //get GMT date time
        $this->saveSetting($date, 'logout_'.$username); //logout time code is username of admin
    }
     /**
     * save your config value
     * @param type $value is value
     * @param string $code is path or code
     */
    public function saveSetting($value, $code = ''){
        $setting_path = '';
        if($code == ''){
            $setting_path = $value;
        }else{
            $setting_path = $code;
        }
        if(is_null($value)) $value = '';
        Mage::getModel('core/config')->saveConfig($setting_path, $value);
    }
    
}