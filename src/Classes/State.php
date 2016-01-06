<?php

namespace Instagram2Vk\Classes;

use Instagram2Vk\Interfaces\StateInterface;
use PDO;

/**
 * Class State
 * Helps set and detect state of published posts from instagram to VK
 *
 * @package Instagram2Vk\Classes
 */
class State implements StateInterface
{

    /**
     * PDO instance
     *
     * @var null
     */
    private $db = null;

    /**
     * Table name to maintain state
     *
     * @var string
     */
    private $table = 'insta2vkState';

    function __construct($sqlite_filename)
    {

        $this->db = new PDO('sqlite:' . $sqlite_filename);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // prepare table structure
        $this->prepare();

    }

    /**
     * Make sure table structure exists
     */
    private function prepare()
    {

        /**
         * Structure:
         * - id (just an int id)
         * - instagram_post_id (instagram ID of the post)
         * - instagram_created_at (when post was created in Instagram - unixtimestamp)
         */
        $this->db->exec("CREATE TABLE IF NOT EXISTS  " . $this->table . "
                        (id INTEGER PRIMARY KEY, instagram_post_id TEXT, instagram_created_at INTEGER)");

    }

    /**
     * Return last processed post, all newer posts are considered as good to repost
     */
    public function getLastProcessedPost()
    {

        $result = $this->db->query('SELECT * FROM ' . $this->table . ' order by instagram_created_at desc limit 1');

        if($row = $result->fetch()) {
            return $row;
        }

        return null;

    }

    /**
     * Save post as processed
     *
     * @param $instagram_id
     * @param $creation_time
     */
    public function addPost($instagram_id, $creation_time)
    {
        $sql = "INSERT INTO " . $this->table . " (instagram_post_id, instagram_created_at)
                VALUES (:post_id, :time)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':post_id', $instagram_id, SQLITE3_TEXT);
        $stmt->bindParam(':time', $creation_time, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * Detect if this post was proceeded before
     *
     * @param $post_id
     */
    public function isProceeded($post_id)
    {

        $query = 'SELECT * FROM ' . $this->table . ' where instagram_post_id=:id';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $post_id, SQLITE3_TEXT);
        $result = $stmt->execute();

        return count($stmt->fetchAll()) != 0;
    }

}