# instagram2vk
Instagram reposter to vk.com does exatly this - schedules reposting of instagram photos to VK.com (on community's wall):

- you can set instagram usernames and tags to gather photos from;
- you can set exact time and weekdays for scheduling posts to VK.com.


Requirements
============
In order to run this script you will need:

- Instagram access_token (make sure that access_token is not given for app in Sandbox mode). Access token must have scopes: `basic` and `public_content`.
- Vk.com access_token for reposting photos (must have rights to post on given wall).


## Example

```php
use GuzzleHttp\Client;
use Instagram2Vk\Classes\State;
use Instagram2Vk\Classes\VkPoster;
use Instagram2Vk\Classes\InstagramCrawler;
use Instagram2Vk\Classes\VkPostTimeScheduler;
use Instagram2Vk\Classes\VkPostTransformer;


$client = new Client(); // guzzle client for HTTP requests
$state = new State("file.sqlite"); // sqlite database for state storage
$transformer = new VkPostTransformer(); // transformer for instagram posts
$scheduler = new VkPostTimeScheduler(); // scheduler for reposting to VK.com
// set schedule table (Weekday => timeslots)
$scheduler->setScheduleTimeSlots(
    [
        "Mon" => ["12:30", "12:40"],
        "Tue" => [],
        "Wed" => [],
        "Thu" => ["21:30"],
        "Fri" => [],
        "Sat" => [],
        "Sun" => [],
    ]
);


// Crawl new data
$dataSource = new InstagramCrawler($client, "ISNTAGRAM_ACCESS_TOKEN", ["tag1", "moscow", "russia"],["username1", "applemusic"]);
$dataSource->crawl(); // start gathering new posts

// Pass data to VK poster
$poster = new VkPoster(
    $scheduler,
    $transformer,
    $dataSource,
    $client,
    $state,
    "VK_ACCESS_TOKEN",
    "VK_COMMUNITY_ID"
);

$poster->run(); // schedule new posts to VK
```


## Support

Please feel free to add PR or email me at meekman74@gmail.com
