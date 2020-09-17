<?php

namespace Goudenvis\SessionFilter;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class Filter
{
    protected static $search = 'search';

    /**
     * @param $key
     * @return bool
     *
     * Check if the session contains the requested key
     *
     */
    public static function hasFilter($key): bool
    {
        return Arr::has(session(self::$search), $key);
    }

    /**
     * @param $key
     * @return mixed
     *
     * Get the data from the session. Even if it is not set
     *
     */
    public static function getFilter($key)
    {
        if ( self::hasFilter($key) )
        {
            return Arr::get(session(self::$search), $key, []);
        }
        return [];
    }

    /**
     * @param $key
     * @param $data
     *
     * Set the filter in the session
     */
    public static function setFilter($key, $data): void
    {
        session()->put(self::$search . $key, $data);
    }

    /**
     * @return array
     *
     * Get daterange from the previous daterange according to the
     * daterange witch is set in the session.
     */
    public static function getPreviousDaterange(): array
    {
        $originalDates = self::applyFilterDaterange();
        $endDate = Carbon::parse($originalDates[0])->subDay();
        $startDate = $endDate->copy();
        $startDate->subDays(count($originalDates));

        return self::makeDaterangeFromDates($startDate, $endDate);
    }

    /**
     * @param bool $fullWeeks
     * @return array
     *
     * Get the daterange from the session.
     */
    public static function applyFilterDaterange($fullWeeks = false)
    {
        if (self::hasFilter('daterange')) {
            $startDate = self::getFilter('daterange.start');
            $endDate = self::getFilter('daterange.end');
        } else {
            $startDate = now()->subMonth();
            $endDate = now()->endOfDay();
        }

        if ($fullWeeks){
            $startDate->startOfWeek();
            $endDate->endOfWeek();
        }

        return self::makeDaterangeFromDates($startDate, $endDate);
    }

    /**
     * @param Carbon $startdate
     * @param Carbon $enddate
     * @return array
     *
     * Get a array with dates between the given dates,
     * including the given dates itself.
     */
    public static function makeDaterangeFromDates(Carbon $startdate, Carbon $enddate, $parsed = false): array
    {
        return self::makeDaterange($startdate->diffInDays($enddate), $enddate, $parsed);
    }

    /**
     * Make a daterange with the first and last day of the month.
     *
     * @param Carbon $startdate
     * @param Carbon $enddate
     * @return array
     */
    public static function makeMonthDaterangeFromDates(Carbon $startdate, Carbon $enddate)
    {
        $startMonth = $startdate->startOfMonth();
        $endMonth = $enddate->endOfMonth();

        $diff = $startMonth->diffInMonths($endMonth);
        $result = [];
        $workdate = $startMonth;

        for ($x = 0; $x <= $diff; $x++) {
            $start = $workdate->copy();

            $end = $workdate->copy();
            $start = $start->addMonths($x)->startOfMonth();
            $end = $end->addMonths($x)->endOfMonth();
            $result[] = ['start' => $start, 'end' => $end];
        }

        return $result;
    }

    /**
     * Make a daterange from numbers.
     * On default start today, and get back 7 days
     * Make individual Carbon instances when $parsed is true
     *
     * @param int $days
     * @param null $startDate
     * @param bool $parsed
     * @return array
     *
     */
    public static function makeDaterange($days = 7, $startDate = null, $parsed = false): array
    {
        $collect = [];
        $date = $startDate ?? Carbon::today();

        for ($i=self::dateRange($days); $i >= 0; $i--){
            $useDate = $date->copy();
            if ($parsed) {
                $collect[] = $useDate->subDays($i);
            } else {
                $collect[] = $useDate->subDays($i)->toDateString();
            }
        }
        return $collect;
    }

    /**
     * @param int $days
     * @return int
     *
     * Give the amount of dates
     */
    protected static function dateRange($days = 30): int
    {
        return Carbon::today()->subDays($days)->diffInDays(Carbon::today());
    }

    /**
     * Apply column keys direct from the session
     *
     * @param Builder $query
     * @param array $keys
     * @return Builder
     */
    protected static function applyFilters(Builder $query, $keys = [])
    {
        foreach ($keys as $key => $column)
        {
            if (self::hasFilter($key)) {
                $query->whereIn($column, self::getFilter($key));
            }
        }
        return $query;
    }
}
