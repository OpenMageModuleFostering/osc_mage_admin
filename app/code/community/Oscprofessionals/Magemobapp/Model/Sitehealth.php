<?php
/**
 *
 * @category   Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Sitehealth extends Mage_Core_Model_Abstract
{
    protected $_dataArray;
    public $days;
    
    //Data reporting days limit from admin setting
    const REPORTING_DAYS = 'magemobapp/settings/reporting_days';
    
    /**
     * @return json array
     * @return sitehealth info.
     */
    public function oscIndex()
    {
		try{
        return $this->getSitehealthInfo();
		}catch(Exception $e){
			Mage::log($e->getMessage() , null, 'oscMageAdim.log');

		}
    }
    
    function getSitehealthInfo()
    {
        $reportingDays = Mage::getStoreConfig(self::REPORTING_DAYS);
        
        $finalArr             = array();
        $website_enabled      = Mage::getStoreConfig('magemobapp/sitehealth/website_enabled');
        $server_enabled       = Mage::getStoreConfig('magemobapp/sitehealth/server_enabled');
        $index_enabled        = Mage::getStoreConfig('magemobapp/sitehealth/index_enabled');
        $cronschedule_enabled = Mage::getStoreConfig('magemobapp/sitehealth/cronschedule_enabled');
        $logtable_enabled     = Mage::getStoreConfig('magemobapp/sitehealth/logtable_enabled');
        
        if ($website_enabled == 1) {
            $website_status             = Mage::getModel('magemobapp/storeinfo')->getWebsiteRunningStatus();
            $finalArr['website_status'] = $website_status;
        }
        
        if ($server_enabled == 1) { //Get CPU usage and Memory Usage
            $server_load             = Mage::getModel('magemobapp/storeinfo')->getCPUMemoryUsage();
            $finalArr['server_load'] = $server_load;
        }
        
        if ($index_enabled == 1) { //get indexing status notification
            $indexing_status             = Mage::getModel('magemobapp/storeinfo')->getIndexingStatus();
            $finalArr['indexing_status'] = $indexing_status;
        }
        
        if ($cronschedule_enabled == 1) { //get cron_schedule status notification
            $cron_schedule             = Mage::getModel('magemobapp/storeinfo')->getCronScheduleInfo();
            $finalArr['cron_schedule'] = $cron_schedule;
        }
        
        if ($logtable_enabled == 1) { //get log table size increase  notification
            $log_info             = Mage::getModel('magemobapp/stock')->getLogTableSizeAlert();
            $finalArr['log_info'] = $log_info;
        }
        $this->_dataArray = $finalArr;
        return $this->_dataArray;
    }
}