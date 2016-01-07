<?php
namespace Instagram2Vk\Interfaces;

/**
 * Interface VkPostTranformerInterface
 * Describes how tranformation should be made for every VK.com's post
 *
 * @package Instagram2Vk\Interfaces
 */
interface VkPostTransformerInterface {

    /**
     * Generate new instance with filled post data
     *
     * @param $post
     * @return mixed
     */
    public static function getInstance($post);

    /**
     * Set post's original data to transform
     *
     * @param $post
     * @return mixed
     */
    public function setPost($post);

    /**
     * Return text to attach to new post
     *
     * @return mixed
     */
    public function getText();

    /**
     * Return image url to image file to attach to new post
     * or null if skip attaching
     *
     * @return mixed
     */
    public function getImageUrl();

    /**
     * Return unique id of this post (for maintaining state)
     *
     * @return mixed
     */
    public function getId();

    /**
     * Return creation time in UNIXTIMESTAMP
     *
     * @return mixed
     */
    public function getCTime();

}