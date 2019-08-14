<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Orders extends Mage_Core_Model_Abstract
{
    protected $_dataArray;
    
    /**
     * @return json array
     * @return orders info (array)
     */
    public function oscIndex($params)
    {
        
        return $this->getOrderInfo($params['order_id']);
    }
    
    /**
     * Get store Order Count
     * @return (string) count
     */
    public function orderCount($days)
    {
        
        switch ($days) {
            
            case "today":
                //get Todays Date
                $fromDate = Mage::helper('magemobapp')->getTodaysDate();
                break;
            case "lastWeek":
                //get 1 week Back date
                $fromDate = Mage::helper('magemobapp')->getLastWeekDate();
                break;
            case "lastMonth":
                //get 1 month Back date
                $fromDate = Mage::helper('magemobapp')->getMonthBackDate();
                break;
            default:
                //get reporting date
                $fromDate = Mage::helper('magemobapp')->getReportingDate();
                
        }
        
        $storeInfo = Mage::getModel('magemobapp/storeinfo');
        // store id
        $storeId = $storeInfo->getStoreId();
        // store id array
        $storeIds = $storeInfo->getStoreIds($storeId);

        $orderCollection = Mage::getModel('sales/order')->getCollection();

        if (isset($storeId) && $storeId != '') {
            $orderCollection->addAttributeToFilter('store_id', array(
                       'in' => $storeIds,
                   ));
            
        }
        
        $orderCollection->addAttributeToFilter('created_at', array(
            'from' => $fromDate
        ));
        $orderCount = $orderCollection->count(); //orders count
        
        return $orderCount;
    }
   
    
    /**
     * Get store Total sales with currency.
     * @return (string) sales
     */
    public function todaysSalesTotal()
    {
        
        //get todays Date
        $fromDate = Mage::helper('magemobapp')->getTodaysDate();
        
        $storeId = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        
        $collection = Mage::getResourceModel('sales/order_collection')->addAttributeToFilter('created_at', array(
            'from' => $fromDate
        ));
        
        if ($storeId != 0) {
            $collection->addAttributeToFilter('store_id', $storeId); //fill store
        }
        //$collection;
        
        $collection->addAttributeToSelect('base_grand_total')->addAttributeToSelect('base_total_refunded')->addAttributeToSelect('base_total_paid');

        
        $data  = $collection->getData();
        $total = 0;
        foreach ($data as $eachData) {
            if (isset($eachData['status']) && $eachData['status'] == 'complete') {
                if ($eachData['base_total_refunded'] == '') {
                    $total += (float) $eachData['base_total_paid'];
                } else {
                    $total += (float) $eachData['base_total_paid'] - (float) $eachData['base_total_refunded'];
                }
            } else {
                $total += (float) $eachData['base_grand_total'];
            }
        }
        return Mage::helper('core')->currency($total, true, false);
    }
    
    
    /**
     * Get order list
     * @return order list (array)
     */
    public function getOrderList()
    {
        
        // store id
        $storeId = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        
        //get reporting date
        $fromDate = Mage::helper('magemobapp')->getReportingDate();
        
        
        $orderCollection = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter('created_at', array(
            'from' => $fromDate
        ))->addAttributeToSort('created_at', 'desc');

        
        if ($storeId != 0) {
            $orderCollection->addAttributeToFilter('store_id', $storeId);
        }
        
        foreach ($orderCollection as $_order) {
            $orderTime          = explode(" ", $_order->getCreatedAt());
            $this->_dataArray[] = array(
                'order_id' => $_order->getId(),
                'increment_id' => $_order->getIncrementId(),
                'order_status' => $_order->getStatus(),
                'order_date' => $_order->getCreatedAt(),
                'order_time' => $orderTime[1],
                'customer_name' => $_order->getCustomerName(),
                'order_total' => Mage::helper('core')->currency($_order->getGrandTotal(), true, false), //order total with currency
                'order_total_value' => number_format($_order->getGrandTotal(), 2)
            );
        }
        //Mage::log($this->_dataArray,null,'orderarray.log');
        return array(
            'last_update_time' => date('Y-m-d H:i:s'),
            'orders' => $this->_dataArray
        );
    }
    public function getOrderNotificationList($params)
    {

        $fromDate        = $params['last_update_time'];
        $storeId         = $params['store'];

        $orderCollection = Mage::getModel('sales/order')->getCollection();

        if (isset($storeId) && $storeId != 0) {
            $orderCollection->addAttributeToFilter('store_id', $storeId);
        }
        $orderCollection->addAttributeToFilter('created_at', array(
            'gt' => $fromDate
        ));
        

        $notification = '';
        $notification = $orderCollection->count();
        
        if ($notification > 0) {
            return $notification;
        }
    }
    
    /**
     * Get order info
     * @return order info (array)
     */
    public function getOrderInfo($orderId = null)
    {
        
        $orderCollection = Mage::getModel('sales/order')->load($orderId);
        $_totalObject    = Mage::getBlockSingleton('magemobapp/order_totals');
        $_totalObject->setOrder($orderCollection);
        $_totalObject->initTotals();
        $totals = $_totalObject->getTotals();
        
        //total info in array data
        foreach ($totals as $_total) {

            $totalArray[] = $_total->getCode() . "=" . Mage::helper('core')->currency($_total->getValue(), true, false);
            //with currency
        }
        
        //arrange total Data
        $totalData     = implode("||", $totalArray);
        $ordered_items = $orderCollection->getAllItems();
        
        $orderedItems = array();
        foreach ($ordered_items as $item) {
            
            if ($item['parent_item_id'] == NULL) {
                
                $orderedItems[] = array(
                    'product_id' => $item->getItemId(),
                    'product_name' => $item->getName(),
                    'product_price' => Mage::helper('core')->currency($item->getPrice(), true, false),
                    'product_qty' => number_format($item->getQtyOrdered())
                );
            }
        }
        
        // collect billing address
        $billingAddress = '';
        if (is_object($orderCollection->getBillingAddress())) {
            $countryId      = $orderCollection->getBillingAddress()->getCountryId();
            $billingAddress = $orderCollection->getBillingAddress()->getData('street') . ",";
            $billingAddress .= $orderCollection->getBillingAddress()->getCity() . ",";
            $billingAddress .= $orderCollection->getBillingAddress()->getRegion() . ",";
            $billingAddress .= $orderCollection->getBillingAddress()->getPostcode() . ",";
            $billingAddress .= Mage::app()->getLocale()->getCountryTranslation($countryId);
        }
        
        // collect shipping address
        $shippingAddress = '';
        if (is_object($orderCollection->getShippingAddress())) {
            $countryId       = $orderCollection->getShippingAddress()->getCountryId();
            $shippingAddress = $orderCollection->getShippingAddress()->getData('street') . ",";
            $shippingAddress .= $orderCollection->getShippingAddress()->getCity() . ",";
            $shippingAddress .= $orderCollection->getShippingAddress()->getRegion() . ",";
            $shippingAddress .= $orderCollection->getShippingAddress()->getPostcode() . ",";
            $shippingAddress .= Mage::app()->getLocale()->getCountryTranslation($countryId);
        }
        
        
        $orderTime        = explode(" ", $orderCollection->getCreatedAt());
        $this->_dataArray = array(
            'order_id' => $orderCollection->getId(),
            'increment_id' => $orderCollection->getIncrementId(),
            'order_status' => $orderCollection->getStatus(),
            'order_date' => $orderCollection->getCreatedAt(),
            'order_time' => $orderTime[1],
            'customer_name' => $orderCollection->getCustomerName(),
            'customer_phone_no' => $orderCollection->getBillingAddress()->getTelephone(),
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress,
            'order_total' => $totalData,
            'order_product' => $orderedItems
        );

        //return ordered info data
        return $this->_dataArray;
    }

    /**
     * Get Order List
     * @return order List with date filter info (array)
     */
    public function getTodaysOrderList()
    {
        $dir = '';
        $storeId  = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        //get Todays Date from helper
        $fromDate = Mage::helper('magemobapp')->getTodaysDate();
        
        $orderCollection = Mage::getModel('sales/order')->getCollection();
        
        $orderCollection->addAttributeToFilter('created_at', array(
            'from' => $fromDate
        ));
        $reverseDir = ($dir == 'DESC') ? 'ASC' : 'DESC';
        $orderCollection->getSelect()->order('increment_id ' . $reverseDir);
        if (isset($storeId) && $storeId != 0) {
            $orderCollection->addAttributeToFilter('store_id', $storeId);
        } /*else {
            $orderCollection = Mage::getModel('sales/order')->getCollection();
        }*/

        foreach ($orderCollection as $_order) {
            $orderTime          = explode(" ", $_order->getCreatedAt());
            $this->_dataArray[] = array(
                'order_id' => $_order->getId(),
                'increment_id' => $_order->getIncrementId(),
                'order_status' => $_order->getStatus(),
                'order_date' => $_order->getCreatedAt(),
                'order_time' => $orderTime[1],
                'customer_name' => $_order->getCustomerName(),
                'order_total' => Mage::helper('core')->currency($_order->getGrandTotal(), true, false), //order total with currency
                'order_total_value' => $_order->getGrandTotal()
            );
        }
        
        return array(
            'last_update_time' => date('Y-m-d H:i:s'),
            'orders' => $this->_dataArray
        );
    }
    
}