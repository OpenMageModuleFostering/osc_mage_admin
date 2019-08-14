<?php
/**
 *
 * @category   Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Notification extends Mage_Core_Model_Abstract
{
    protected $_dataArray;
    
    //Data reporting days limit from admin setting
    const REPORTING_DAYS = 'magemobapp/settings/reporting_days';
    
    /**
     * @return json array
     * @return Notification info.
     */
    public function oscIndex($params)
    {
        return $this->getNotification($params);
    }
    
    /**
     * Get Notification info
     * @return Notification (array)
     */
    function getNotification($params)
    {
        $finalNotificationArr = array();
        $orderNotification    = '';
        $healthNotification   = '';
        $orderNotification = $this->orderNotification($params);
        
        $healthNotification  = $this->healthNotification();
        $websiteNotification = $this->websiteNotification();
        
        $finalNotificationArr['notification'] = array();
        
        if ($orderNotification != '') {
            $order                                         = Mage::getModel('magemobapp/orders');
            $newOrdercount                                 = $order->orderCount('today');
            $finalNotificationArr['notification']['order'] = array(
                'badge' => '1',
                'label' => 'New Order received',
                'totalTodaysOrder' => $newOrdercount,
                'description' => $orderNotification
            );
        }
        
        if (sizeof($healthNotification) > 0) {
            $finalNotificationArr['notification']['sitehealth'] = array(
                'badge' => '2',
                'label' => 'Site Health Monitor',
                'description' => $healthNotification
            );
        }
        
        if (sizeof($websiteNotification) > 0) {
            $finalNotificationArr['notification']['webpage'] = array(
                'badge' => '3',
                'label' => 'Web Page Monitor',
                'description' => $websiteNotification
            );
        }
        
        $finalNotificationArr['last_update_time'] = date('Y-m-d H:i:s');
        
        $this->_dataArray = $finalNotificationArr;
        return $this->_dataArray;
    }
    function orderNotification($params)
    {
        $ordernotify = Mage::getModel('magemobapp/orders')->getOrderNotificationList($params);
        if ($ordernotify) {
            $ordernotify = 'You have received ' . $ordernotify . ' new order.';
            return $ordernotify;
        }
        
    }
    function websiteNotification()
    {
        $notification_enabled = Mage::getStoreConfig('magemobapp/settings/notification_enabled');
        if ($notification_enabled == 1) {
            
            $website_enabled = Mage::getStoreConfig('magemobapp/sitehealth/website_enabled');
            
            if ($website_enabled == 1) {
                $websiteNotification = Mage::getModel('magemobapp/storeinfo')->getWebsiteNotification();
                
                if ($websiteNotification != '') {
                    $websiteNotification = substr($websiteNotification, 0, (sizeOF($websiteNotification) - 3));
                    $websiteNotification = explode('::', $websiteNotification);
                    return $websiteNotification;
                }
            }
        }
        else {
            return array();
        }
    }
    function healthNotification()
    {
        $notification_enabled = Mage::getStoreConfig('magemobapp/settings/notification_enabled');
        $server_enabled       = Mage::getStoreConfig('magemobapp/sitehealth/server_enabled');
        $logtable_enabled     = Mage::getStoreConfig('magemobapp/sitehealth/logtable_enabled');
        $index_enabled        = Mage::getStoreConfig('magemobapp/sitehealth/index_enabled');
        $cpustatus            = '';
        $logNote              = '';
        $indexNote            = '';
        
        if ($notification_enabled == 1) {
            if ($server_enabled == 1) {
                $cpustatus = Mage::getModel('magemobapp/storeinfo')->getCPUMemoryUsageNotification();
            }
            if ($logtable_enabled == 1) {
                $logNote = Mage::getModel('magemobapp/stock')->getLogTableNotification();
            }
            if ($index_enabled == 1) {
                $indexNote = Mage::getModel('magemobapp/storeinfo')->getIndexingStatusNotification();
            }
            $notification       = $logNote . $indexNote . $cpustatus;
            $notification       = substr($notification, 0, (sizeOF($notification) - 3));
            $healthNotification = explode('::', $notification);
            
        }
        return $healthNotification;
    }
}