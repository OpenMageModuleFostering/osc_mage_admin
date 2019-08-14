<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Login
{
    
    protected $_helper;
    protected $_dataArray;
    
    public function __construct()
    {
        $this->_helper = Mage::helper('magemobapp');
    }
    
    
    /**
     * login process
     * @param usename and password
     * @return sucess login Data
     */
    public function login($username, $password)
    {
        $username = base64_decode($username);
        $password = base64_decode($password);
        
        $tmpUser = '';
        for ($i = 0; $i < strlen($username); $i++) {
            if ($i % 2 == 0) {
                $tmpUser .= $username[$i];
            }
        }
        
        $username = $tmpUser;
        $tmpPass = '';
        for ($i = 0; $i < strlen($password); $i++) {
            if ($i % 2 == 0) {
                $tmpPass .= $password[$i];
            }
        }
        
        $password = $tmpPass;
        $session = Mage::getSingleton('admin/session');
        $Login   = false;
        
        if ($this->isAuthenticate($username, $password)) {
            if (empty($username) || empty($password)) {
                return;
            }
            /** @var $user Mage_Admin_Model_User */
            $user = Mage::getModel('admin/user');
            $user->login($username, $password);
            if ($user->getId()) {
                if (method_exists($session, 'renewSession')) {
                    $session->renewSession();
                }
                $session->setIsFirstPageAfterLogin(true);
                $session->setUser($user);
                $session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
                
                $Login = true;
            }
        }
        if ($Login) {
            $this->saveLogin();
            //clear cache
            Mage::app()->getCacheInstance()->cleanType("config");
            $loginData = $this->loginDataProcess($username);
            return $loginData;
        }
        throw new Exception($this->_helper->__('Login failed'), 6);
    }
    
    
    /**
     * save config for earch admin time login
     */
    public function saveLogin()
    {
        $username = Mage::getSingleton('admin/session')->getUser()->getUsername();
        $datetime = Mage::app()->getLocale()->date();
        $datetime->setTimezone('Etc/UTC');
        $last = $this->getLastLogin();
        Mage::getSingleton('admin/session')->setLastLogin($last); // save into session
        $this->saveSetting($datetime->toString(Varien_Date::DATETIME_INTERNAL_FORMAT), 'login_' . $username); //login time code is username of admin
    }
    
    
    /**
     * get login date time
     * @return string
     */
    public function getLastLogin()
    {
        $code           = Mage::getSingleton('admin/session')->getUser()->getUsername();
        $date           = Mage::app()->getLocale()->date()->subDay(1)->setMinute(0)->setSecond(0); // get time from yestoday when never logout
        $last_yesterday = gmdate("Y-m-d H:i:s", $date->getTimestamp()); //datetime mysql
        if (($loginconfig = $this->getSetting('login_' . $code)) != '') {
            return $loginconfig;
        } else {
            return $last_yesterday;
        }
    }

    /**
     * get setting
     * @param type $code
     * @return type
     */
    public function getSetting($code)
    {
        $setting_path = $code;
        return Mage::getStoreConfig($setting_path);
    }

    /**
     * save your config value
     * @param type $value is value
     * @param string $code is path or code
     */
    public function saveSetting($value, $code = '')
    {
        $setting_path = '';
        if ($code == '') {
            $setting_path = $value;
        } else {
            $setting_path = $code;
        }
        if (is_null($value))
            $value = '';
        Mage::getModel('core/config')->saveConfig($setting_path, $value);
    }

    /**
     * check is loged in
     * @return boolean
     */
    public function isCheckLogin()
    {
        $session = Mage::getSingleton('admin/session');
        
        if ($session->isLoggedIn()) {
            return true;
        }
        return false;
    }
    
    /**
     * check ACL
     */
    public function isCheckRole()
    {
        // bind call name to controller name for check acl
        $session = Mage::getSingleton('admin/session');
        if (!$session->isAllowed('magemobapp/api')) {
            return false;
        }
        return true;
    }
    
    
    /**
     * API authenticate
     * @param type $username
     * @param type $password
     * @return boolean
     * @throws Mage_Core_Exception
     */
    protected function isAuthenticate($username, $password)
    {
        $config = Mage::getStoreConfigFlag('admin/security/use_case_sensitive_login');
        $result = false;
        $user   = Mage::getModel('admin/user')->loadByUsername($username);
        try {
            $sensitive = ($config) ? $username == $user->getUsername() : true;
            if ($sensitive && $user->getId() && Mage::helper('core')->validateHash($password, $user->getPassword())) {
                
                if ($user->getIsActive() != '1') {
                    Mage::throwException(Mage::helper('magemobapp')->__('This account is inactive.'));
                }
                if (!$user->hasAssigned2Role($user->getId())) {
                    $result = false;
                } else {
                    $result = true;
                }
            }
        }
        catch (Mage_Core_Exception $e) {
            $user->unsetData();
            throw $e;
        }
        return $result;
    }
    
    
    /**
     * Process Login Data from multipal class as per requirement.
     * @param login username
     * @return process array
     */
    public function loginDataProcess($username = null)
    {
        
        //get session id
        $session   = Mage::getSingleton('admin/session');
        $sessionId = $session->getSessionId();
        
        //store Data from storeinfo class
        $subStoreData = Mage::getModel('magemobapp/storeinfo')->getWebsite();
        
        $defaultStoreName = $_SERVER['HTTP_HOST'];
        
        $subStore = array();
        
        foreach ($subStoreData as $subStoreEach) {
            $subStore[] = ($subStoreEach['name'] . '=' . $subStoreEach['default_store_id']);
        }
        
        $defaultStore = explode('=', $subStore[0]);
        
        $subStoreArray = implode("||", $subStore);

        $params['store'] = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        $store_id        = '';
        if (isset($params['store']) && $params['store'] != '') {
            $store_id = $params['store'];
        }
        
        $path     = Mage::getStoreConfig('design/header/logo_src', $store_id);
        //get skin path
        $skinBase = Mage::getDesign()->getSkinBaseDir() . DS . $path;

        $path = Mage::getDesign()->getSkinUrl($path);
        
        if (!file_exists($skinBase)) {
            $storeLogo = '';
            
        } else {
            $data = file_get_contents($path);
            $datapath = explode('.', $path);
            $modifypath = end($datapath);
            $extension = strtolower($modifypath);
            $storeLogo = base64_encode($data);
            $storeLogo = 'data:image/' . $extension . ';base64,' . $storeLogo;
        }
        
        $reportingDays        = Mage::getStoreConfig('magemobapp/settings/reporting_days');
        $notification_enabled = Mage::getStoreConfig('magemobapp/settings/notification_enabled');
        $update_frequency     = Mage::getStoreConfig('magemobapp/settings/update_frequency');
        $last_update_time     = date('Y-m-d H:i:s');
        
        $this->_dataArray = array(
            'session_id' => $sessionId,
            'store_info' => array(
                'store_user_name' => $username,
                'store_label' => 'Demo Store',
                'sub_stores' => $subStoreArray,
                'default_store' => $defaultStore[1],
                'store_logo' => $storeLogo,
                'reporting_days' => $reportingDays,
                'notification' => $notification_enabled,
                'update_frequency' => $update_frequency,
                'last_update_time' => $last_update_time
            )
        );
        return $this->_dataArray;
    }
}