<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */

// collect all stock related Data
class Oscprofessionals_Magemobapp_Model_Stock extends Mage_Core_Model_Abstract
{
    protected $_productArray;
    protected $_stockAlertCount;
    
    //stock alert qty from admin setting
    const STOCK_ALERT = 'magemobapp/settings/stock_critical_level';
    const TABLE_SIZE = 'magemobapp/settings/log_table_size';
    
    /**
     * @return json array
     * @return inventory info (string)
     */
    public function oscIndex($params)
    {
        return $this->getProductsStockAlert($params);
        
    }
    
    /**
     * Get stock alert collection
     * @Product array
     */
    public function getProductsStockAlert($params)
    {
        $storeId = $params['store'];
        $orderby = $params['orderby'];
        $sortby  = $params['sortby'];

        $dir = '';
        $stockAlertQty = Mage::getStoreConfig(self::STOCK_ALERT);
        
        $collection = Mage::getModel('catalog/product')->getCollection()->setStoreId($storeId)->addStoreFilter($storeId)->addAttributeToSelect('*'); // select all attributes
        
        
        if (is_array($storeId) && !empty($storeId)) {
            $collection->addFieldToFilter('store_id', array(
                'in' => $storeId
            ));
        }
        
        if ($params['sortby'] == 'asc') {
            if ($params['orderby'] == 'name') {
                $collection->addAttributeToSort('name', 'ASC');
            } else {
                $collection->getSelect()->joinLeft(array(
                    '_inventory_table' => 'cataloginventory_stock_item'
                ), "_inventory_table.product_id = e.entity_id ", array(
                    'qty'
                ))->order(array(
                    '_inventory_table.qty ASC'
                ));
                $reverseDir = ($dir == 'DESC') ? 'ASC' : 'DESC';
                $collection->getSelect()->order('qty ' . $reverseDir);
            }
        } else {
            if ($params['orderby'] == 'name') {
                $collection->addAttributeToSort('name', 'DESC');
            } else {
                $collection->getSelect()->joinLeft(array(
                    '_inventory_table' => 'cataloginventory_stock_item'
                ), "_inventory_table.product_id = e.entity_id ", array(
                    'qty'
                ))->order(array(
                    '_inventory_table.qty DESC'
                ));
                $reverseDir = ($dir == 'ASC') ? 'DESC' : 'ASC';
                $collection->getSelect()->order('qty ' . $reverseDir);
            }
        }
        $this->_stockAlertCount = 0;
        foreach ($collection as $product) {
            $stocklevel = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
            
            if ($stocklevel <= $stockAlertQty && $stocklevel >= 0) {
                $this->_productArray[] = array(
                    'product_id' => $product->getId(),
                    'product_name' => $product->getName(),
                    'product_qty' => $stocklevel
                );
                $this->_stockAlertCount++;
            }
        }

        return $this->_productArray;
        
    }
    /*BOF by developer 127*/
    /*
     ** Get log table size alert
     *
     */
    public function getLogTableSizeAlert()
    {
        $logTableSize = Mage::getStoreConfig('magemobapp/sitehealth/log_table_size');

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $tables = array(
            'log_customer',
            'log_quote',
            'log_summary',
            'log_summary_type',
            'log_url',
            'log_url_info',
            'log_visitor',
            'log_visitor_info',
            'log_visitor_online'
        );
        
        
        $log_table_result = array();
        $j                = 0;
        foreach ($tables as $table) {

            if ($connection->isTableExists($table)) {
                $select = $connection->select()->from($table);
                $abc    = $connection->fetchAll($select);
                $size   = count($abc);

            }
            
            if ($size >= $logTableSize) {
                $msg = 'High';
            } else {
                $msg = 'Normal';
            }
            
            $log_table_result[$j]['tablename'] = $table;
            $log_table_result[$j]['count']     = $size;
            $log_table_result[$j]['status']    = $msg;
            $j++;
        }

        return $log_table_result;
        
    }
    /*
     ** Get log table size Notification
     *
     */
    public function getLogTableNotification()
    {
        $notify = $this->getLogTableSizeAlert();
        
        $notification = '';
        
        $total = 0;
        foreach ($notify as $statuskey => $statusval) {
            $stval = $statusval['status'];
            
            if ($stval == 'High') {
                $total++;
            }
            
        }
        if ($total) {
            $notification = 'Currently ' . $total . ' log tables are on critical level.::';
        }
        
        return $notification;
    }
    /**
     * Get count stock alert
     * @return stock count
     */
    public function getCountStockAlert()
    {
        $params['store'] = Mage::getModel('magemobapp/storeinfo')->getStoreId();
        $this->getProductsStockAlert($params['store']);
        return $this->_stockAlertCount;
    }
}