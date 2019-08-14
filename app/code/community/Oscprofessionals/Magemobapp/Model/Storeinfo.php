<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */

class Oscprofessionals_Magemobapp_Model_Storeinfo extends Mage_Core_Model_Abstract
{
    protected $_storeList = '';
    protected $_storeId = '';
    protected $_storeIds = '';
    /**
     * @return json array
     * @return inventory info (array)
     */
    public function oscIndex($params)
    {
        return $this->getSubStore();
    }
    
    /**
     * Get sub store collection
     * @return array
     */
    public function getSubStore()
    {
        $stores = Mage::app()->getStores(true);
        
        foreach ($stores as $store) {
            $name               = $store->getName();
            $id                 = (int) $store->getId();
            $this->_storeList[] = array(
                'store_id' => $id,
                'store_name' => $name
            );
        }
        
        return $this->_storeList;
        
    }
    
    /**
     * Get store Id
     * @return Store ID
     */
    
    public function getStoreId()
    {
        $params = Mage::app()->getRequest()->getPost('params');
        if (isset($params['store']) && $params['store'] != '') {
        $this->_storeId = $params['store']; //$storeId = $_POST;
        return $this->_storeId;
        }
    }
    
    /**
     * Get website Id
     * @return Website ID
     */
    public function getWebsite()
    {
        $visitors = Mage::getModel("core/store_group")->getCollection();
        $result   = array();
        foreach ($visitors as $val) {
            $result[] = $val->toArray();
        }
        return $result;
    }
    
    /**
     * Get website Id
     * @return Website ID
     */
    public function getStoreIds($storeId = null)
    {
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $website = Mage::getModel('core/website')->load($websiteId);
        $this->_storeIds = $website->getStoreIds();
        return $this->_storeIds;
    }
    /**
     * Get indexing status
     * @return indexing status  (array)
     */
    public function getIndexingStatus()
    {
        $resArr     = array();
        $collection = Mage::getResourceModel('index/process_collection');
        foreach ($collection as $key => $item) {
            if (!$item->getIndexer()->isVisible()) {
                $collection->removeItemByKey($key);
                continue;
            }
            $item->setName($item->getIndexer()->getName());
            $item->setDescription($item->getIndexer()->getDescription());
            $item->setUpdateRequired($item->getUnprocessedEventsCollection()->count() > 0 ? 1 : 0);
            if ($item->isLocked()) {
                $item->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
            }
            $resArr[] = $item->toArray();
        }
        return $resArr;
    }
    
    public function getIndexingStatusNotification()
    {
        $notify       = $this->getIndexingStatus();
        $notification = '';
        $total        = 0;
        foreach ($notify as $statuskey => $statusval) {
            $stval = $statusval['status'];
            
            if ($stval == 'require_reindex') {
                $total++;
            }
        }
        if ($total) {
            if($total >1){
                $notification = 'Currently ' . $total . ' indexing are required.::';
            }else{
                $notification = 'Currently ' . $total . ' indexing is required.::';
            }
        }

        return $notification;
    }
    /**
     * Get Cron schedule info 
     * @return onlinevisitors  (array)
     */
    public function getCronScheduleInfo()
    {
        $modelCollection = $this->getAllCodes();

        $cronCollection  = Mage::getModel('cron/schedule')->getCollection();
        $cronCollection->getSelect()->group('job_code');
        
        $result = array();
        foreach ($cronCollection as $val) {
            $modelCode = $val->toArray();
            if (isset($modelCollection[$modelCode['job_code']]) && $modelCollection[$modelCode['job_code']] != '') {
            $val['model'] = $modelCollection[$modelCode['job_code']];
            $result[] = $val->toArray();
            }
        }
        
        return $result;
    }
    
    public function getAllCodes()
    {
        $codes  = array();
        $config = Mage::getConfig()->getNode('crontab/jobs');

        if ($config instanceof Mage_Core_Model_Config_Element) {
            foreach ($config->children() as $jobcode => $tmp) {
                $model               = (array) $tmp->run;
                $modelArray[$jobcode] = $model['model'];
            }
            return $modelArray;
        }
    }
    protected function _visit($url)
    {
        $agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
        $ch    = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $page     = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 200 && $httpcode < 300)
            return true;
        else
            return false;
    }
    protected function _checkParse($url)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $output = curl_exec($ch);
        
        curl_close($ch);
        
        
        $result = simplexml_load_string($output, 'SimpleXmlElement', LIBXML_NOERROR + LIBXML_ERR_FATAL + LIBXML_ERR_NONE);
        if (false == $result)
            return 'error';
    }

    public function getWebsiteRunningStatus()
    {
        $homepage             = Mage::getBaseUrl();
        $_productCollection   = $this->_getSingleProductsCollection();
        $productUrl           = $_productCollection->getProductUrl();
        $categoryIds          = $_productCollection->getCategoryIds();
        $categoryUrl          = $this->_getCategoryUrl($categoryIds);
        $urlArray = array(
            'homepage' => $homepage,
            'listingpage' => $categoryUrl,
            'productpage' => $productUrl
        );

        foreach ($urlArray as $key => $value) {
            
            $visitUrl       = $this->_visit($value);
            $checkParseUrl = $this->_checkParse($value);
            if ($visitUrl) {
                $error = "Website OK" . "\n";
                if ($checkParseUrl == 'error') {
                    $error = 3; //"Website Having Error"."\n";(parse error)
                } else {
                    $error = 1; //"Website Okay"; (Running)
                }
            } else {
                $error = 2; //"Website DOWN"; (Network error)
            }
            
            $newArray[$key] = array(
                'name' => $key,
                'value' => $error
            );
        }
        
        return $newArray;
        
    }
    
    /*
     ** Get Website Notification
     *
     */
    public function getWebsiteNotification()
    {
        
        $notify = $this->getWebsiteRunningStatus();
        
        foreach ($notify as $statuskey => $statusval) {
            $stval = $statusval['value'];

            $title = '';
            if($statuskey == 'homepage'){
                $title = 'Home page';
            }elseif($statuskey == 'listingpage'){
                $title = 'Listing page';
            }elseif($statuskey == 'productpage'){
                $title = 'Product page';
            }
            
            if ($stval == '2') {
                
                $notification = "Seems Network error on " . $title . '.::';
            }
            if ($stval == '3') {
                
                $notification .= "Seems Parse error on " . $title . '.::';
            }
            
        }
        return $notification;
    }
    public function serverUptime()
    {
        $data = '';
        $current_reading = @exec('uptime');
        $uptime          = explode(' up ', $current_reading);
        $uptime          = explode(',', $uptime[1]);
        $uptime          = $uptime[0] . ', ' . $uptime[1];
        $data .= "$uptime";
        return $data;
    }
    
    public function get_server_memory_usage()
    {
        
        $free         = shell_exec('free');
        $free         = (string) trim($free);
        $free_arr     = explode("\n", $free);
        $mem          = explode(" ", $free_arr[1]);
        $mem          = array_filter($mem);
        $mem          = array_merge($mem);
        $memory_usage = $mem[2] / $mem[1] * 100;
        
        return $memory_usage;
    }
    public function loadServer()
    {
        $current_reading = @exec('uptime');
        preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $current_reading, $averages);
        
        $data = "$averages[1], $averages[2], $averages[3]\n";
        
        return $data;
    }
    function get_server_cpu_usage()
    {
        
        $load = sys_getloadavg();
        return $load[0];
        
    }
    
    /*Get CPU usage and Memory Usage*/
    public function getCPUMemoryUsage()
    {
        
        $memory        = $this->get_server_memory_usage();
        $memory1       = round($memory, 2) . '%';
        $cpu           = $this->get_server_cpu_usage();
        $cpu1          = round($cpu, 2) . '%';
        $load          = $this->loadServer();
        $server        = $this->serverUptime();

        $server_load   = Mage::getStoreConfig('magemobapp/sitehealth/server_load');
        $memory_usage  = Mage::getStoreConfig('magemobapp/sitehealth/memory_usage');
        $cpu_usage     = Mage::getStoreConfig('magemobapp/sitehealth/cpu_usage');
        $load          = explode(',', $load);
        $loadStatus    = 'ok';
        $memory1Status = 'ok';
        $cpu1Status    = 'ok';
        $loadtext      = '';
        
        for ($i = 0; $i < sizeof($load); $i++) {
            if ($load[$i] >= $server_load) {
                $loadStatus = 'critical';
            }
            $loadtext .= trim($load[$i]) . '%, ';
        }
        
        if ($memory1 >= 0.1) {
            $memory1Status = 'critical';
        }
        
        if ($cpu1 >= $cpu_usage) {
            $cpu1 = 'critical';
        }
        $cpu_memory_usage = array(
            'memory_usage' => array(
                'name' => 'Memory Usage',
                'value' => $memory1,
                'status' => $memory1Status
            ),
            'cpu_usage' => array(
                'name' => 'CPU Usage',
                'value' => $cpu1,
                'status' => $cpu1Status
            ),
            'load' => array(
                'name' => 'Load Average',
                'value' => $loadtext,
                'status' => $loadStatus
            ),
            'server_uptime' => array(
                'name' => 'Server Uptime',
                'value' => $server
            )
        );
        
        
        return $cpu_memory_usage;
    }
    public function getCPUMemoryUsageNotification()
    {
        $notification = '';
        $notify = $this->getCPUMemoryUsage();
        foreach ($notify as $statuskey => $statusval) {
        if (isset($statusval['status']) && $statusval['status'] != '') {
            $stval = $statusval['status'];
            if ($stval == 'critical') {
                $notification .= "Memory usage  " . $statusval['value'] . '.::';
            }
        }
        }
        return $notification;
        
    }

	protected function _getSingleProductsCollection()
	{
	    $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToSort('entity_id', 'DESC');
        $collection->setPage(1,1); //set a limit to 1 so the query would be faster
        return $collection->getFirstItem();
    }

	protected function _getCategoryUrl($ids)
	{
	    $categoryId = (isset($ids[0]) ? $ids[0] : 0);
		$collection = Mage::getModel('catalog/category')->load($categoryId);
		return $collection->getUrl();
    }
}