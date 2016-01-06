<?php

use Instagram2Vk\Classes\State;

class StateTest extends PHPUnit_Framework_TestCase {

    function test_state() {

        // create in memory
        $state = new State(":memory:");
        $this->assertNull($state->getLastProcessedPost());

        $data = [
            [
                "post_id" => "ABCD",
                "created_at" => strtotime("today 12:00")
            ],
            [
                "post_id" => "WYZ",
                "created_at" => strtotime("today 12:01")
            ],
            [
                "post_id" => "SSE",
                "created_at" => strtotime("today 11:59")
            ]
        ];

        foreach($data as $item) {
            $state->addPost($item['post_id'], $item['created_at']);
        }

        $post = $state->getLastProcessedPost();

        $this->assertEquals($post['instagram_post_id'], 'WYZ');

        $this->assertTrue($state->isProceeded($data[0]['post_id']));
        $this->assertFalse($state->isProceeded("SOME_ID"));

    }

}