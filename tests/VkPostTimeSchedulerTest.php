<?php

use Instagram2Vk\Classes\VkPostTimeScheduler;

class VkPostTimeSchedulerTest extends PHPUnit_Framework_TestCase
{

    private $slots = [];

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        // prepare empty stack
        $this->slots = [
            "Mon" => [],
            "Tue" => [],
            "Wed" => [],
            "Thu" => [],
            "Fri" => [],
            "Sat" => [],
            "Sun" => [],
        ];
    }


    /**
     * Test that next timeslot is generated correctly
     */
    function test_scheduler_gives_correct_timeslot_in_1_hour()
    {

        $now = strtotime("26.11.2015 12:00");

        // add next slot
        $next_today = "13:00";
        $this->slots[date("D", $now)][] = $next_today;

        $vkPostTimeScheduler = new VkPostTimeScheduler();
        $vkPostTimeScheduler->setLastTimeslot($now);
        $vkPostTimeScheduler->setScheduleTimeSlots($this->slots);

        $this->assertEquals(strtotime($next_today, $now), $vkPostTimeScheduler->getNextTimeSlot());

    }

    /**
     * Test that next timeslot is generated correctly
     */
    function test_scheduler_gives_correct_timeslots_in_row()
    {

        $now = strtotime("26.12.2015 12:00");

        $now_plus_1d = strtotime("next day", $now);
        $now_plus_2d = strtotime("next day", $now_plus_1d);
        $now_plus_3d = strtotime("next day", $now_plus_2d);
        $now_plus_4d = strtotime("next day", $now_plus_3d);
        $now_plus_5d = strtotime("next day", $now_plus_4d);
        $now_plus_6d = strtotime("next day", $now_plus_5d);
        $now_plus_7d = strtotime("next day", $now_plus_6d);

        // add next slot
        $this->slots[date("D", $now)] = ["12:00"];
        $this->slots[date("D", $now_plus_1d)] = ["13:00", "19:22"];
        $this->slots[date("D", $now_plus_2d)] = ["00:10"];
        $this->slots[date("D", $now_plus_4d)] = ["23:59"];
        $this->slots[date("D", $now_plus_6d)] = ["0:00"];

        $vkPostTimeScheduler = new VkPostTimeScheduler();
        $vkPostTimeScheduler->setLastTimeslot($now);
        $vkPostTimeScheduler->setScheduleTimeSlots($this->slots);

        $this->assertEquals(strtotime("13:00", $now_plus_1d), $vkPostTimeScheduler->getNextTimeSlot());
        $this->assertEquals(strtotime("19:22", $now_plus_1d), $vkPostTimeScheduler->getNextTimeSlot());
        $this->assertEquals(strtotime("00:10", $now_plus_2d), $vkPostTimeScheduler->getNextTimeSlot());
        $this->assertEquals(strtotime("23:59", $now_plus_4d), $vkPostTimeScheduler->getNextTimeSlot());
        $this->assertEquals(strtotime("0:00", $now_plus_6d), $vkPostTimeScheduler->getNextTimeSlot());
        $this->assertEquals(strtotime("12:00", $now_plus_7d), $vkPostTimeScheduler->getNextTimeSlot());

    }

    /**
     * Test that is no timeslots given then exception is generated
     */
    function test_scheduler_no_timeslots_exception()
    {

        $vkPostTimeScheduler = new VkPostTimeScheduler();

        $this->setExpectedException('Instagram2Vk\Exceptions\NoTimeslotsException');

        $vkPostTimeScheduler->setScheduleTimeSlots($this->slots);

    }


    /**
     * Test that wrong weekday is throwing an exception
     * Like "mon" is wrong and "Mon" is okay
     */
    function test_scheduler_wrong_weekday_exception()
    {

        $vkPostTimeScheduler = new VkPostTimeScheduler();

        $this->setExpectedException('Instagram2Vk\Exceptions\BadTimeslotException');

        $vkPostTimeScheduler->setScheduleTimeSlots([
            "Mon" => [], //okay
            "fri" => []// wrong
        ]);

    }

    /**
     * Test that wrong timeslots is throwing an exception
     * Like "mon" is wrong and "Mon" is okay
     */
    function test_scheduler_timeslots_exception()
    {

        $vkPostTimeScheduler = new VkPostTimeScheduler();

        $this->setExpectedException('Instagram2Vk\Exceptions\BadTimeslotException');

        $vkPostTimeScheduler->setScheduleTimeSlots([
            "Mon" => [], //okay
            "Fri" => ""// wrong
        ]);


    }

    /**
     * Test that wrong timeslots is throwing an exception
     * Like "mon" is wrong and "Mon" is okay
     */
    function test_scheduler_timeslots_wrong_time_exception()
    {

        $vkPostTimeScheduler = new VkPostTimeScheduler();

        $this->setExpectedException('Instagram2Vk\Exceptions\BadTimeslotException');

        $vkPostTimeScheduler->setScheduleTimeSlots([
            "Mon" => [], //okay
            "Fri" => ["25:12"] // wrong
        ]);


    }

}