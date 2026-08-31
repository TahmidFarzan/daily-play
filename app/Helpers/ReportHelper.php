<?php
namespace App\Helpers;

use Carbon\Carbon;
use App\Helpers\SystemHelper;

class ReportHelper
{
    public static function frequencyFormDateRange($startDate, $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $frequency = "Date Range: {$start->format('d-M-Y')} To {$end->format('d-M-Y')}";

        $isFullMonthStart = $start->day === 1;
        $isFullMonthEnd   = $end->day === $end->daysInMonth;
        $isFullYear = $start->day === 1 && $start->month === 1 && $end->day === $end->daysInMonth && $end->month === 12;

        if ($start->isSameDay($end)) {
            $frequency = "Date: {$start->format('d-M-Y')}";
        }
        elseif ($isFullYear && $start->year === $end->year) {
            $frequency = "Year: {$start->year}";
        } elseif ($isFullYear && $start->year !== $end->year) {
            $frequency = "Years (Range): {$start->year} To {$end->year}";
        }
        elseif ($isFullMonthStart && $isFullMonthEnd && ! $isFullYear) {
            if ($start->year === $end->year && $start->month === $end->month) {
                $frequency = "Month: {$start->format('M - Y')}";
            } else {
                $frequency = "Months (Range): {$start->format('M - Y')} to {$end->format('M - Y')}";
            }
        } else {
            $frequency = "Date Range: {$start->format('d-M-Y')} To {$end->format('d-M-Y')}";
        }
        return $frequency;
    }
}
