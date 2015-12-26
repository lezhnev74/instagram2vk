<?php
namespace Instagram2Vk\Classes;

use Instagram2Vk\Exceptions\BadTimeslotException;
use Instagram2Vk\Interfaces\VkPostTimeScheduleInterface;
use Instagram2Vk\Exceptions\NoTimeslotsException;

class VkPostTimeScheduler implements VkPostTimeScheduleInterface
{

    private $timeslots = [];
    private $lastTimeslot = null;

    function __construct()
    {
        $this->lastTimeslot = time();
    }

    /**
     * Set configuration data of time slots
     * Accepts array where keys are weekdays in format date("D")
     * If the key is missed - this day will not have publications
     *
     * @param $slots
     * @return mixed
     */
    public function setScheduleTimeSlots(array $slots)
    {
        $this->validateSlots($slots);
        $this->timeslots = $slots;
    }

    /**
     * Set last timeslot to start with
     * Otherwise should start from now()
     *
     * @param $unixtimestamp
     * @return mixed
     */
    public function setLastTimeslot($unixtimestamp)
    {
        $this->lastTimeslot = $unixtimestamp;
    }

    /**
     * Return next timeslot for publication in unixtimestamp
     *
     * @return int
     */
    public function getNextTimeSlot()
    {
        if (!count($this->timeslots)) {
            throw new NoTimeslotsException();
        }

        $timeslots_unix = [];

        // transform given timeslots to UNIX timestamps in the nearest future
        foreach ($this->timeslots as $weekday => $day_timeslots) {

            // modifier for use in "strtotime"
            $weekday_time_modifier = (date("D", $this->lastTimeslot) == $weekday) ? "today" : "next " . $weekday;

            // for each timeslot on this day turn it to the UNIX timestamp (relative to last timeslot)
            foreach ($day_timeslots as $slot) {
                // get slot unix timestamp (relative to lastTimeslot)
                $slot_ux = strtotime($weekday_time_modifier . " " . $slot, $this->lastTimeslot);

                // skip slots that are in the past
                if ($slot_ux > $this->lastTimeslot) {
                    $timeslots_unix[] = $slot_ux;
                }
            }

        }

        // sort resulting unix timeslots by nearest to current time
        sort($timeslots_unix, SORT_NUMERIC);

        // save as lastTimestamp
        $this->lastTimeslot = $timeslots_unix[0];

        // and return
        return $this->lastTimeslot;

    }

    /**
     * Validate that given timeslots is given
     */
    private function validateSlots($slots)
    {

        // this variable will be untouched if no good timestamp found in timeslots
        $found_good_timeslot = false;
        $good_keys = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];


        foreach ($slots as $week_day => $timestamps) {

            if (!in_array($week_day, $good_keys)) {
                throw new BadTimeslotException("Weekday expected as date('D') return but found: " . $week_day);
            }

            // timestamp MUST be as array
            if (!is_array($timestamps)) {
                throw new BadTimeslotException("Array expected, but found: " . $timestamps);
            }

            foreach ($timestamps as $timestamp) {
                // make sure timeslot has good time value like "22:50" and not "25:60".
                if (!preg_match("#^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$#", $timestamp)) {
                    throw new BadTimeslotException("Timestamp on " . $week_day . " has bad value: " . $timestamp);
                } else {
                    $found_good_timeslot = true;
                }
            }
        }

        if (!$found_good_timeslot) {
            throw new NoTimeslotsException();
        }

    }


}