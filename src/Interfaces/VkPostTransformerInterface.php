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
     * Return image path to local image file to attach to new post
     * or null if skip attaching
     *
     * @return mixed
     */
    public function getImagePath();

}