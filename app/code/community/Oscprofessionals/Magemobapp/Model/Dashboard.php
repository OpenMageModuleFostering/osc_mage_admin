<?php
/**
 *
 * @category   Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Dashboard extends Mage_Core_Model_Abstract
{
    protected $_dataArray;
    public $days;
    
    //Data reporting days limit from admin setting
    const REPORTING_DAYS = 'magemobapp/settings/reporting_days';
    
    /**
     * @return json array
     * @return dashboard info.
     */
    public function oscIndex()
    {
        
        return array(
            'last_update_time' => date('Y-m-d H:i:s'),
            'dashboard' => $this->getDashboardInfo()
        );
    }
    
    /**
     * Get dashboard info
     * @return dashboard info (array)
     */
    function getDashboardInfo()
    {
        
        
        //get order Model
        $order = Mage::getModel('magemobapp/orders');
        
        //get customers Model
        $customers = Mage::getModel('magemobapp/customers');
        
        //Recent order count with date filter from order class
        $newOrdercount = $order->orderCount('today');

        
        //get all order count
        $orderCount = $order->orderCount('');

        //get customers count
        $newCustomersCount = $customers->customerCount('today');
        
        //get all customer count
        $customerCount = $customers->customerCount();
        
        //get count stock alert
        $stockAlertCount    = Mage::getModel('magemobapp/stock')->getCountStockAlert();

        //get online customer(visitors) count
        $onlinestatusCount  = Mage::getModel('magemobapp/customers')->getOnlineCustomers();

        //get abandoned cart customer count
        $abandonedcartCount = Mage::getModel('magemobapp/customers')->getCountAbandonedCustomers();
        
        //get store total sales
        $salesTotal = $order->todaysSalesTotal();
        $neworder_enabled      = Mage::getStoreConfig('magemobapp/dashboard/neworder_enabled');
        $allorder_enabled      = Mage::getStoreConfig('magemobapp/dashboard/allorder_enabled');
        $newcustomers_enabled  = Mage::getStoreConfig('magemobapp/dashboard/newcustomers_enabled');
        $allcustomers_enabled  = Mage::getStoreConfig('magemobapp/dashboard/allcustomers_enabled');
        $stockalert_enabled    = Mage::getStoreConfig('magemobapp/dashboard/stockalert_enabled');
        $todayssales_enabled   = Mage::getStoreConfig('magemobapp/dashboard/todayssales_enabled');
        $reportingdays_enabled = Mage::getStoreConfig('magemobapp/dashboard/reportingdays_enabled');
        $online_enabled        = Mage::getStoreConfig('magemobapp/dashboard/online_enabled');
        $abandonedcart_enabled = Mage::getStoreConfig('magemobapp/dashboard/abandonedcart_enabled');
        
        if ($neworder_enabled == 1) {
            $finalArr['new_order'] = $newOrdercount;
        }
        
        if ($allorder_enabled == 1) {
            $finalArr['all_orders'] = $orderCount;
        }
        
        if ($newcustomers_enabled == 1) {
            $finalArr['new_customers'] = $newCustomersCount;
        }
        if ($allcustomers_enabled == 1) {
            $finalArr['all_customers'] = $customerCount;
        }
        if ($stockalert_enabled == 1) {
            $finalArr['stock_alert'] = $stockAlertCount;
        }
        if ($todayssales_enabled == 1) {
            $finalArr['today_sales'] = $salesTotal;
        }
        if ($online_enabled == 1) {
            $finalArr['online_customers'] = $onlinestatusCount;
        }
        if ($abandonedcart_enabled == 1) {
            $finalArr['abandoned_cart'] = $abandonedcartCount;
        }
        
        $this->_dataArray = $finalArr;
        return $this->_dataArray;
    }
    
}