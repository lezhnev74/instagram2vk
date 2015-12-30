<?php

namespace Instagram2Vk\Classes;

use InvalidArgumentException;
use GuzzleHttp\ClientInterface;
use Instagram2Vk\Exceptions\InstagramException;

/**
 * Class InstagramCrawler
 * Requests needed media
 *
 * @package Instagram2Vk\Classes
 */
class InstagramCrawler
{
    /**
     * Client to work with HTTP
     *
     * @var null
     */
    private $client = null;

    /**
     * Tags to gather media from
     *
     * @var array
     */
    private $tags = [];

    /**
     * Usernames to gather media from
     *
     * @var array
     */
    private $users = [];
    /**
     * Maps username to its id
     *
     * @var array
     */
    private $names_to_id_map = [];

    /**
     * Token to make requests to API
     *
     * @var null
     */
    private $access_token = null;


    /**
     * InstagramCrawler constructor.
     * Put settings while instantiating
     *
     * @param $access_token for instagram API
     * @param $tags
     * @param $user_ids User IDs or usernames
     */
    public function __construct(ClientInterface $http_client, $access_token, $tags = [], $user_ids = [])
    {
        // client to make requests using GuzzleHttp\ClientInterface
        $this->client = $http_client;

        $this->tags = array_filter($tags, function ($item) {
            return strlen(trim($item));
        });
        $this->users = array_filter($user_ids, function ($item) {
            return strlen(trim($item));
        });

        $this->access_token = $access_token;

        // make sure we hace input for crawling
        if (!count($this->tags) && !count($this->users)) {
            throw new InvalidArgumentException("No input data: tags and users are empty");
        }


    }


    /**
     * Return gathered media from resources
     *
     * @return array
     */
    public function crawl()
    {
        $media = [
            "users" => [],
            "tags"  => [],
        ];

        // translate usernames to UserIds if possible
        $this->validateUserIds();

        // Gather tag's media
        foreach ($this->tags as $tag_name) {
            // filter first symbols if needed
            $tag_name = str_replace("#", "", $tag_name);

            $media['tags'][$tag_name] = $this->getMediaForTag($tag_name);
        }

        // Gather user's media
        foreach ($this->users as $username) {
            // filter first symbols if needed
            $tag_name = str_replace("@", "", $username);

            $media['users'][$this->getUserName($username)] = $this->getMediaForUser($username);
        }

        return $media;
    }

    /**
     * Return recent media for given tag
     *
     * @param $tag
     * @return array
     */
    private function getMediaForTag($tag, $count = 20)
    {

        $url = 'https://api.instagram.com/v1/tags/' . $tag . '/media/recent';
        $media = $this->request($url, ['count' => $count]);

        return $media;
    }

    /**
     * Return recent media for given username
     *
     * @param $username
     * @return array
     */
    private function getMediaForUser($username, $count = 20)
    {

        $url = 'https://api.instagram.com/v1/users/' . $username . '/media/recent';
        $media = $this->request($url, ['count' => $count]);

        return $media;
    }


    /**
     * Make request, return media data as array
     *
     * @param $url
     * @param $query_agrs
     * @return array
     */
    private function request($url, $query_agrs = [])
    {

        // add access token to this request
        $query_agrs['access_token'] = $this->access_token;

        $response = $this->client->request('GET', $url, [
            'query' => $query_agrs,
            'http_errors' => false // do not throw exception of answer
        ]);

        // expects json output

        $response_body = json_decode($response->getBody()->getContents(), true);

        // if answer is not code-200
        if (isset($response_body['meta']) && $response_body['meta']['code'] != 200) {
            throw new InstagramException($response_body['meta']['error_type'] . ": " . $response_body['meta']['error_message'],
                $response_body['meta']['code']);
        }

        if (!isset($response_body['data'])) {
            throw new InstagramException("Response has bad format (code " . $response->getStatusCode() . "): " . $url);
        }

        return $response_body['data'];
    }

    /**
     * If userId is given as a string - then try to translate it to real numeric ID
     */
    private function validateUserIds()
    {

        foreach ($this->users as $i => $user) {
            if (!preg_match("#^\d+$#", $user)) {
                $url = 'https://api.instagram.com/v1/users/search';
                $response_users = $this->request($url, ['q' => $user]);

                $response_users_filtered = array_filter($response_users, function ($response_user) use ($user) {
                    return $response_user['username'] == $user;
                });

                if (count($response_users_filtered)) {
                    $user_id = $response_users_filtered[0]['id'];
                    $this->names_to_id_map[$user_id] = $user;
                    $this->users[$i] = $user_id;
                } else {
                    throw new InstagramException("Unable to translate username ".$user." to it's instagram ID");
                }

            }
        }

    }

    /**
     * Return string username if it was supplied
     *
     * @param $id
     */
    private function getUserName($id) {

        if(array_key_exists($id, $this->names_to_id_map)) {
            return $this->names_to_id_map[$id];
        }

        return $id;
    }

}