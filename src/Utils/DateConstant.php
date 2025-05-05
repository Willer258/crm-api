<?php

namespace App\Utils;

class DateConstant
{

    public function isConvertible($data)
    {
        return DateConstant::get($data) !== null;
    }

    static public function get($date)
    {
        switch ($date) {
            case 0:
            case 'today': {
                    return new \DateTime();
                    break;
                }
            case 1:
            case 'tomorrow': {

                    $now = new \DateTime();
                    $now->modify('+1 day');
                    return $now;
                    break;
                }
            case 2:
            case 'first_day_current_month': {
                    return date('d-m-Y', strtotime('first day of this month'));
                    break;
                }
            case 3:
            case 'last_day_current_month': {
                    return date('d-m-Y', strtotime('last day of this month'));
                    break;
                }
            // case 4:
            // case 'first_day_next_month': {
            //         return date('d-m-Y', strtotime('last day of this month'));
            //         break;
            //     }
            // case 5:
            // case 'last_day_next_month': {
            //         return date('d-m-Y', strtotime('last day of this month'));
            //         break;
            //     }
        }
        return null;
    }
}
