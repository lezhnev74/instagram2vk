<?php

namespace Instagram2Vk\Classes;

use Exception;
use InvalidArgumentException;
use GuzzleHttp\ClientInterface;
use SimpleDownloader\Classes\Downloader;
use Instagram2Vk\Exceptions\VkException;
use Instagram2Vk\Interfaces\StateInterface;
use SimpleDownloader\Exceptions\FileException;
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
     * State class
     *
     * @var null
     */
    private $state = null;

    /**
     * Class to download remote file to local path
     *
     * @var null
     */
    private $downloader = null;

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
     * Postponed posts for <group_id> group
     *
     * @var array
     */
    private $postponed = null;

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
        StateInterface $state,
        $vk_access_token,
        $group_id
    ) {
        $this->scheduler = $scheduler;
        $this->transformer = $transformer;
        $this->dataSource = $dataSource;
        $this->client = $http_client;
        $this->state = $state;
        $this->access_token = $vk_access_token;
        $this->group_id = $group_id;

        $this->downloader = new Downloader();
    }

    /**
     * Start scheduling
     */
    public function run()
    {
        // gather postponed posts
        $this->postponed = $this->getPostponed();

        // detect last published time
        $lastDate = $this->getLastPublishedTime($this->postponed);
        $this->scheduler->setLastTimeslot($lastDate);

        // get data from data source
        foreach ($this->dataSource->getData() as $i => $item) {

            $transformer = call_user_func([$this->transformer, 'getInstance'], $item);

            // check that this post was not yet posted
            if (!$this->state->isProceeded($transformer->getId())) {
                //echo "Ok, process ".$transformer->getId()." ".$transformer->getText()."\n";
                $this->post($transformer);
            }

        }

    }

    /**
     * Make publication to VK.com
     *
     * @param $transformer
     */
    private function post($transformer)
    {

        $image_url = $transformer->getImageUrl();
        $file_name = md5($transformer->getId()) . "_image.jpg";
        $file_path = __DIR__ . "/";//sys_get_temp_dir();

        // download image to local folder
        try {
            $this->downloader->downloadFile($image_url, $file_path, $file_name);

            $attachment_id = $this->uploadAsAttachment($file_path . $file_name);

            // now schedule the post
            $this->schedulePost($transformer, $attachment_id);

            // now update state
            $this->state->addPost($transformer->getId(), $transformer->getCTime());

            // clean up downloaded file (on success)
            if (file_exists($file_path . $file_name)) {
                unlink($file_path . $file_name);
            }

            return true;
        } catch (FileException $e) {
            echo "We have a problem with file: " . $e->getMessage() . " \n";
        } catch (InvalidArgumentException $e) {
            echo "Wrong arguments are passed: " . $e->getMessage() . " \n";
            //echo $e->getTraceAsString();
        } catch (Exception $e) {
            echo "Something bad happened: " . $e->getMessage() . " \n";
            //echo $e->getTraceAsString();
        }

        // clean up downloaded file (on error)
        if (file_exists($file_path . $file_name)) {
            unlink($file_path . $file_name);
        }

        return null;
    }


    /**
     * Schedule the post
     *
     * @param $transformer
     * @param $attachment_id
     */
    private function schedulePost($transformer, $attachment_id)
    {

        $method = "wall.post";
        $params = [
            "owner_id"     => "-" . $this->group_id,
            "from_group"   => "1",
            "message"      => $transformer->getText(),
            "attachments"  => $attachment_id,
            "publish_date" => $this->scheduler->getNextTimeSlot(),
        ];

        $response = $this->request($method, $params);

//      var_dump($response);

    }

    /**
     * Uplaod image to VK.com and get attachment_id
     *
     * @param $file_path
     */
    private function uploadAsAttachment($file_path)
    {


        // Ref: https://vk.com/dev/photos.getWallUploadServer
        $method = "photos.getWallUploadServer";
        $params = [
            "group_id" => $this->group_id,
        ];

        $upload_data = $this->request($method, $params);

        if (!isset($upload_data['upload_url'])) {
            // problem with returned format
            return null;
        }

        $upload_url = $upload_data['upload_url'];

        // now make request to Upload server
        $uploaded_response = $this->client->request('POST', $upload_url, [
            'multipart' => [
                [
                    'name'     => 'photo',
                    'contents' => fopen($file_path, 'r'),
                ],
            ],
        ]);

        $response_body = $uploaded_response->getBody()->getContents();
        //var_dump($response_body);
        $json = json_decode($response_body, true);

        // now save photo to wall
        $method = "photos.saveWallPhoto";
        $params = [
            "group_id" => $this->group_id,
            "photo"    => $json['photo'],
            "server"   => $json['server'],
            "hash"     => $json['hash'],
        ];

        $save_response = $this->request($method, $params);

        if (!count($save_response)) {
            // upload failed
            return null;
        }

        // return attachment id
        return $save_response[0]['id'];

    }


    /**
     * Get date of latest post on the wall (unixtimestamp)
     *
     * @param array $posts
     */
    private function getLastPublishedTime($posts)
    {

        if (count($posts)) {

            usort($posts, function ($a, $b) {
                return $b['date'] - $a['date'];
            });

            // return latest date for post (in unixtimestamp)
            return $posts[0]['date'];

        }

        return time();

    }

    /**
     * Get postponed posts
     *
     * @param int $count
     * @throws VkException
     */
    private function getPostponed($count = 100)
    {
        // API limit to 100
        if ($count > 100) {
            $count = 100;
        }

        $method = "wall.get";
        $params = [
            "owner_id" => "-" . $this->group_id, // as of https://vk.com/dev/wall.get
            "count"    => $count,
            "filter"   => "postponed",
            "extended" => '1',
        ];

        $data = $this->request($method, $params);

        if (isset($data['wall'])) {
            // remove first argument with counter
            array_shift($data['wall']);

            // return data array only
            return $data['wall'];
        }

        return null;

    }

    /**
     * Make request to API
     *
     * @param $api_method
     * @param $query_args
     * @return mixed
     * @throws VkException
     */
    private function request($api_method, $query_args)
    {

        $query_args['access_token'] = $this->access_token;
        $query_args['version'] = '5.42';

        $url = "https://api.vk.com/method/" . $api_method;

        $response = $this->client->request('GET', $url, [
            'query'       => $query_args,
            'http_errors' => false // do not throw exception of answer
        ]);

        // dump full requested URL
        // echo $url . "?" . \GuzzleHttp\Psr7\build_query($query_args) . "\n\n";

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