<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

/**
 * TimeDate helpers for Sugar
 */
class supp_TimeDate extends \TimeDate
{
    /**
     * Given a date string, this will try to convert it first from user time and then try db time
     * @param $date
     * @return bool|\DateTime|\SugarDateTime
     */
    public function convertToDateTime($date)
    {
        $returnDate = false;
        if (is_string($date)) {
            $returnDate = $this->fromUser($date);
            if (!is_object($returnDate)) {
                $returnDate = $this->fromDb($date);
            }
        } elseif ($date instanceof \DateTime || $date instanceof \SugarDateTime) {
            $returnDate = $date;
        }

        return $returnDate;
    }
}