<?php
/**
 *
 * @category    Oscprofessionals
 * @package    Oscprofessionals_Magemobapp
 * @author     : Oscprofessionals Team <oscpteam@oscprofessionals.com>
 */
class Oscprofessionals_Magemobapp_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    //Data reporting days limit from admin setting
    const REPORTING_DAYS = 'magemobapp/settings/reporting_days';
    
    /**
     * get from day with date Filter
     * @param type $filter
     * @return type
     */
    public function getTodaysDate($daysFilter = null)
    {
        
        $fromDate = Mage::app()->getLocale()->date();
        $fromDate->subDate(0);
        $date = gmdate("Y-m-d", $fromDate->getTimestamp());
        return $date;
        
    }
    
    
    /**
     * get last day with date Filter
     * @param type $filter
     * @return type
     */
    public function getLastWeekDate($daysFilter = null)
    {
        
        $fromDate = Mage::app()->getLocale()->date();
        $fromDate->subDate(7);
        $date = gmdate("Y-m-d", $fromDate->getTimestamp());
        return $date;
        
    }
    
    
    /**
     * get 1 month Back date for filter
     * @param type $filter
     * @return type
     */
    public function getMonthBackDate()
    {
        
        $currentDate = Mage::app()->getLocale()->date();
        $currentDate->subMonth(1);
        $fromDate = date('Y-m-d H:i:s', strtotime($currentDate));
        
        return $fromDate;
    }

    /**
     * get back date with admin reporting days.
     * @param type $filter
     * @return type
     */
    public function getReportingDate()
    {
        $reportingDays = Mage::getStoreConfig(self::REPORTING_DAYS);
        $fromDate = Mage::app()->getLocale()->date();

        if(($reportingDays > 30) || empty($reportingDays)){
          $reportingDays = 30;
        }
        $date = gmdate("Y-m-d", $fromDate->getTimestamp());
        // minus from current date to reporting date
        if($reportingDays > 0){
            $reportingDays = ($reportingDays - 1);
        }else{
            $reportingDays;
        }
        $beforeDate = "-".$reportingDays.'days';
        $date = date('Y-m-d', strtotime($beforeDate, strtotime($date)));
        return $date;
    }
}