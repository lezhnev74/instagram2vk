<?php
namespace Instagram2Vk\Interfaces;

/**
 * Class VkPostTimeScheduleInterface
 * Interface for classes who can calculate timeslots for future posts
 *
 * @package Instagram2Vk\Interfaces
 */
interface VkPostTimeScheduleInterface {

    /**
     * Set configuration data of time slots
     *
     * @param $slots
     * @return mixed
     */
    public function setScheduleTimeSlots(array $slots);

    /**
     * Set last timeslot to start with
     * Otherwise should start from now()
     *
     * @param $unixtimestamp
     * @return mixed
     */
    public function setLastTimeslot($unixtimestamp);


    /**
     * Return next timeslot for publication in unixtimestamp
     *
     * @return int
     */
    public function getNextTimeSlot();
}