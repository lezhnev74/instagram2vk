<?php
namespace Instagram2Vk\Classes;

use Instagram2Vk\Interfaces\VkPostTransformerInterface;

class VkPostTransformer implements VkPostTransformerInterface {

    private $post = null;

    /**
     * Set post's original data to transform
     *
     * @param $post
     * @return mixed
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * Return text to attach to new post
     *
     * @return mixed
     */
    public function getText()
    {
        // TODO: Implement getText() method.
    }

    /**
     * Return image path to local image file to attach to new post
     * or null if skip attaching
     *
     * @return mixed
     */
    public function getImagePath()
    {
        // TODO: Implement getImagePath() method.
    }

}