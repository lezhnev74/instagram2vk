<?php

namespace Instagram2Vk\Interfaces;


/**
 * Interface StateInterface
 * Rules for state classes to maintain a state of the system
 *
 * @package Instagram2Vk\Interfaces
 */
interface StateInterface {

    /**
     * Return row with last proceeded post
     *
     * @return mixed
     */
    public function getLastProcessedPost();

    /**
     * Save this id as proceeded
     *
     * @param $uniq_id
     * @param $creation_time
     * @return mixed
     *
     */
    public function addPost($uniq_id, $creation_time);

    /**
     * Detect if given post id was proceeded already
     *
     * @param $uniq_id
     * @return mixed
     */
    public function isProceeded($uniq_id);

}