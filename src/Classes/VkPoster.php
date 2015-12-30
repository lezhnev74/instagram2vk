<?php

namespace Instagram2Vk\Classes;

use GuzzleHttp\ClientInterface;
use Instagram2Vk\Exceptions\VkException;
use Instagram2Vk\Interfaces\DataSourceInterface;
use Instagram2Vk\Interfaces\VkPostTimeScheduleInterface;
use Instagram2Vk\Interfaces\VkPostTransformerInterface;

/**
 * Class VkPoster
 * Posts data to Vk.com API
 *
 * @package Instagram2Vk\Classes
 */
class VkPoster
{

    /**
     * Class that generates timestamps for scheduling
     *
     * @var VkPostTimeScheduleInterface|null
     */
    private $scheduler = null;

    /**
     * Class that transforms data from DataSource to Vk.com posts
     *
     * @var VkPostTransformerInterface|null
     */
    private $transformer = null;

    /**
     * Class that gives array of data to schedule
     *
     * @var DataSourceInterface|null
     */
    private $dataSource = null;

    /**
     * Http client to make requests to Vk.com API
     *
     * @var ClientInterface|null
     */
    private $client = null;

    /**
     * Access_token for Vk.com API
     *
     * @var null
     */
    private $access_token = null;

    /**
     * Set group id for publishing data to
     *
     * @var null
     */
    private $group_id = null;

    function __construct(
        VkPostTimeScheduleInterface $scheduler,
        VkPostTransformerInterface $transformer,
        DataSourceInterface $dataSource,
        ClientInterface $http_client,
        $vk_access_token,
        $group_id
    ) {
        $this->scheduler = $scheduler;
        $this->transformer = $transformer;
        $this->dataSource = $dataSource;
        $this->client = $http_client;
        $this->access_token = $vk_access_token;
        $this->group_id = $group_id;
    }

    /**
     * Start scheduling
     */
    public function run()
    {

        // detect last published time
        $lastDate = $this->getLastPublishedTime();

        // get data from data source
        foreach ($this->dataSource->getData() as $item) {

            $transformer = call_user_func([$this->transformer, 'getInstance'], $item);

            // $transformer->getText()
            // $transformer->getImageUrl()


        }

    }


    /**
     * Get date of latest post on the wall
     */
    private function getLastPublishedTime()
    {

        $method = "wall.get";
        $params = [
            "owner_id" => "-" . $this->group_id, // as of https://vk.com/dev/wall.get
            "count"    => 100,
            "filter"   => "postponed",
            "extended" => '1',
        ];

        $data = $this->request($method, $params);

        if (isset($data['wall'])) {

            $count = array_shift($data['wall']);

            if($count) {

                usort($data['wall'], function ($a, $b) {
                    return $b['date'] - $a['date'];
                });

                // return latest date for post (in unixtimestamp)
                return $data['wall'][0]['date'];

            }
        }

        return time();

    }

    private function request($api_method, $query_params)
    {

        $query_params['access_token'] = $this->access_token;
        $query_params['version'] = '5.42';

        $url = "https://api.vk.com/method/" . $api_method;

        $response = $this->client->request('GET', $url, [
            'query'       => $query_params,
            'http_errors' => false // do not throw exception of answer
        ]);

        // expects json output

        $body = $response->getBody()->getContents();
        $response_body = json_decode($body, true);


        if ($response->getStatusCode() != 200) {
            throw new VkException("Failed request for method: " . $api_method);
        }

        if (!isset($response_body['response'])) {
            throw new VkException("Method returned bad response: " . $body);
        }

        return $response_body['response'];

    }


}