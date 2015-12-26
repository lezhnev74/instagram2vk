<?php

use Instagram2Vk\Classes\VkPostTransformer;

class VkPostTransformerTest extends PHPUnit_Framework_TestCase
{

    function test_vk_formatter_with_image()
    {

        $posts = [
            [
                "title" => "Hello there!",
                "text"  => "Hi, what's up there? #tag1",
                "image" => "path_to_image",
            ],
        ];

        $vkFormatter = new VkPostTransformer();
        $vkFormatter->setPost($posts[0]);

        $this->assertEquals($vkFormatter->getText(), $posts[0]['text']);
        $this->assertFileExists($vkFormatter->getImagePath());

    }

    function test_vk_formatter_with_no_image()
    {

        $posts = [
            [
                "title" => "Hello there!",
                "text"  => "Hi, what's up there? #tag1",
                "image" => null,
            ],
        ];

        $vkFormatter = new VkPostTransformer();
        $vkFormatter->setPost($posts[0]);

        $this->assertEquals($vkFormatter->getText(), $posts[0]['text']);
        $this->assertNull($vkFormatter->getImagePath());

    }


}
