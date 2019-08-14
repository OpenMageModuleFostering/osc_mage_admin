<?php
/**
 *
 * @category    Oscprofessionals
 * @package     Oscprofessionals_Magemobapp
 * @author      Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Model_Customers extends Mage_Core_Model_Abstract
{
    protected $_dataArray;
    public $days = '';
    
    /**
     * @return json array
     * @return customers info (array)
     */
    public function oscIndex($params)
    {
        
        return $this->getCustomerInfo($params['customer_id']);
    }
    
    /**
     * Get store customer Count
     * @return (string) count
     */
    public function customerCount($days = '')
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
        
        //store Id
        $storeId = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        
        $customersCollection = Mage::getModel('customer/customer')->getCollection()->addAttributeToFilter('created_at', array(
            'from' => $fromDate
        ));
        
        $customersCount = 0;
        if ($storeId != 0) {
            $customersCollection->addAttributeToFilter('store_id', $storeId);
        }
        $customersCount = $customersCollection->count(); //customers count
        
        return $customersCount;
        
    }
    
    
    /**
     * Get order info
     * @return customers info (array)
     */
    public function getCustomerList()
    {
        
        //get store ID
        $storeId = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        
        //reporting date
        $fromDate = Mage::helper('magemobapp')->getReportingDate();
        
        $customerCollection = Mage::getModel('customer/customer')->getCollection()->addAttributeToFilter('created_at', array(
            'from' => $fromDate
        ))->addAttributeToSort('created_at', 'desc');
        $customerCollection->addAttributeToSelect(array(
            'firstname',
            'lastname'
        ));
        
        if ($storeId != 0) {
            $customerCollection->addAttributeToFilter('store_id', $storeId); //fill store
        }
        
        foreach ($customerCollection as $_customer) {
            $customer = Mage::getModel('customer/customer')->load($_customer->getId()); //insert cust ID
            foreach ($customer->getAddresses() as $address) {
                $telephone   = $address->getTelephone();
                $countryCode = $address->getCountryId();
                $countryName = Mage::app()->getLocale()->getCountryTranslation($countryCode);
            }
            
            //get order commection with customer ID
            $orderCount = $this->getCustomerTotalOrder($_customer->getId());
            
            $this->_dataArray[] = array(
                'customer_id' => $_customer->getId(),
                'customer_name' => $_customer->getName(),
                'customer_email' => $_customer->getEmail(),
                'customer_total_order' => $orderCount,
                'customer_phone_no' => $telephone,
                'customer_location' => $countryName,
                'customer_registration_date' => $_customer->getCreatedAt()
            );
        }
        return $this->_dataArray;
        
    }
    
    
    /**
     * Get customer info
     * @return customer info (array)
     */
    public function getCustomerInfo($customerId = null)
    {
        $telephone = '';
        $countryName = '';
        
        $customer = Mage::getModel('customer/customer')->load($customerId); //insert cust ID
        $customerData = $customer->getData();

        //get order commection with customer ID
        $orderCount = $this->getCustomerTotalOrder($customer->getId());

        //get customer created_date
        $createdAt = $customer->getCreatedAt();
        $createdAt = Mage::helper('core')->formatDate($createdAt, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);

        //get customer total sales
        $customerTotalsSale = $this->getLifetimeSales($customer->getId());
        
        $customerOrderData = $this->getCustomersOrders($customer->getId());
        
        //customer address object
        $addressData = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
        
        // "long" country name
        $country_id = $addressData->getCountryId();
        $address    = $addressData['street'] . ",";
        $address .= $addressData->getCity() . ",";
        $region = $addressData->getRegion();

        if (!empty($region)) {
            $address .= $region . ",";
        }

        $address .= $addressData->getPostcode() . ",";
        $address .= Mage::app()->getLocale()->getCountryTranslation($country_id);

        foreach ($customer->getAddresses() as $customerAddress) {
            $telephone   = $customerAddress->getTelephone();
            $countryCode = $customerAddress->getCountryId();
            $countryName = Mage::app()->getLocale()->getCountryTranslation($countryCode);
        }

        $this->_dataArray = array(
            'customer_id' => $customer->getId(),
            'customer_name' => $customer->getName(),
            'customer_email' => $customer->getEmail(),
            'customer_total_order' => $orderCount,
            'customer_phone_no' => $telephone,
            'customer_location' => $countryName,
            'customer_registration_date' => $createdAt,
            'customer_billing_address' => $address,
            'customer_total_sale' => Mage::helper('core')->currency($customerTotalsSale, true, false), //customer sales with currency;
            'customer_orders' => $customerOrderData
        );
        //return customer info data
        return $this->_dataArray;
        
    }
    
    /**
     * get customer total order count
     * @param type $customer_id
     * @return customer order count
     */
    public function getCustomerTotalOrder($customerId)
    {
        
        //get store ID
        $storeId = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        
        $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customerId);
        
        if ($storeId != 0) {
            $orders->addAttributeToFilter('store_id', $storeId); //fill store
        }
        
        $orderCount = $orders->count(); //orders count
        
        return $orderCount;
    }
    
    
    /**
     * get life time sales value of customer info
     * @param type $customerId
     * @return customer Life Time sale
     */
    public function getLifetimeSales($customerId)
    {
        
        $collection = Mage::getResourceModel('sales/order_collection');
        
        $collection->addAttributeToFilter('customer_id', $customerId);
        
        //fetch data
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'total' => 'SUM(base_grand_total)'
        ))->group("{$this->mainTable}.customer_id");
        $first_item = $collection->getFirstItem();
        
        return $first_item->getData('total');
    }
    
    
    /**
     * get orders details from customer ID
     * @param type $customerId
     * @return customer order Data
     */
    public function getCustomersOrders($customerId)
    {
        $dir = '';
        $collection = Mage::getModel("sales/order")->getCollection()->addFieldToFilter('customer_id', $customerId);
        $reverseDir = ($dir == 'DESC') ? 'ASC' : 'DESC';
        $collection->getSelect()->order('created_at ' . $reverseDir);

        foreach ($collection as $_customerData) {
            $this->_dataArray[] = array(
                'order_id' => $_customerData->getId(),
                'increment_id' => $_customerData->getIncrementId(),
                'order_date' => $_customerData->getCreatedAt(),
                'order_status' => $_customerData->getStatus(),
                'order_total' => Mage::helper('core')->currency($_customerData->getGrandTotal(), true, false)
            );
        }
        
        return $this->_dataArray;
        
    }
    
    /**
     * Get Online visitors
     * @return onlinevisitors  (array)
     */
    public function getOnlineCustomers()
    {
        
        $params['store'] = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        $returning = 0;
        $new       = 0;
        $total     = 0;
        
        $storeId = '';
        if (isset($params['store']) && $params['store'] != '') {
            $storeId = $params['store'];
        }
        
        $customers    = Mage::getModel('log/visitor_online')->getCollection();
        $read         = Mage::getSingleton('core/resource')->getConnection('core_read');
        $visitorTable = $read->getTableName('log_visitor');
        $table        = 'main_table';
        $customers->getSelect()->joinInner(array(
            'lvt' => $visitorTable
        ), "lvt.visitor_id = {$table}.visitor_id", array(
            'last_visit_at',
            'store_id'
        ));
        
        $customers->addFieldToFilter('store_id', $storeId);

        
        $onlineStatus = array();
        foreach ($customers as $val) {
            $onlinestatus = $val->toArray();
            if ($onlinestatus['visitor_type'] == 'c') {
                $returning++;
            }
            if ($onlinestatus['visitor_type'] == 'v') {
                $new++;
            }
            $total++;
            
        }
        $output = array(
            'new_customer' => $new,
            'returnig_customer' => $returning,
            'total_customer' => $total
        );
        return $output;
        

    }
    /**
     * Get Abandoned Customers
     * @return Abandoned Customers  (array)
     */
    public function getAbandonedCustomersList($params)
    {
        $fromDate = Mage::helper('magemobapp')->getReportingDate();
        $collection = Mage::getResourceModel('reports/quote_collection');
        
        
        $storeIds = '';
        $dir = '';
        if (isset($params['store']) && $params['store'] != '') {
            $storeIds = Mage::getModel('magemobapp/storeinfo')->getStoreIds($params['store']);
        }

        $reverseDir = ($dir == 'DESC') ? 'ASC' : 'DESC';
        $collection->getSelect()->where('main_table.created_at>=?',$fromDate)->order('main_table.created_at ' . $reverseDir);
        
        $collection->prepareForAbandonedReport($storeIds);

        $cartCollection = $collection->load()->toArray();
        

        $result = array();
        foreach ($cartCollection['items'] as $key => $val) {
            $result[$key]['entity_id']          = $cartCollection['items'][$key]['entity_id'];
            $result[$key]['store_id']           = $cartCollection['items'][$key]['store_id'];
            $result[$key]['created_at']         = $cartCollection['items'][$key]['created_at'];
            $result[$key]['items_count']        = $cartCollection['items'][$key]['items_count'];
            $result[$key]['customer_firstname'] = $cartCollection['items'][$key]['customer_firstname'];
            $result[$key]['customer_lastname']  = $cartCollection['items'][$key]['customer_lastname'];
            $result[$key]['customer_email']     = $cartCollection['items'][$key]['customer_email'];
            $result[$key]['customer_is_guest']  = $cartCollection['items'][$key]['customer_is_guest'];
            $result[$key]['grand_total']        = Mage::helper('core')->currency($cartCollection['items'][$key]['grand_total'], true, false);
            $result[$key]['remote_ip']          = $cartCollection['items'][$key]['remote_ip'];
        }

        return $result;
    }
    /**
     * Get count Abandoned Customers
     * @return Abandoned Customers count
     */
    public function getCountAbandonedCustomers()
    {
        $params['store']    = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        $abandonedcart      = $this->getAbandonedCustomersList($params);
        $abandonedcartCount = count($abandonedcart);
        return $abandonedcartCount;
    }
}