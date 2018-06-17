<?php
namespace TwitchClient\API\Kraken\Services;

use TwitchClient\API\Kraken\Kraken;
use stdClass;
use DateTime;
use TwitchClient\Client;

/**
 * Class Users
 * @package TwitchClient\API\Kraken\Services
 */
class Users extends Service
{
    const SERVICE_NAME = "users";

    /**
     * @var array  user names => user IDs match table cache,
     * to avoir duplicating requests when doing operations on the same channel multiple times
     */
    protected static $userIdCache = [];

    /**
     * Users constructor.
     * @param Kraken $kraken
     */
    public function __construct(Kraken $kraken)
    {
        parent::__construct($kraken);
    }

    /**
     * Gets info on an user.
     * This method uses an user ID in-memory cache to avoid requesting IDs from Twitch more than once. It will use
     * the cache to allow fetching the user info in at most 1 request.
     *
     * @param string|int $usernameOrId The nickname of the user, or it's user ID. To use an user ID, you must give it
     *                                 as an integer. Can be omitted when a default target exists, then it will
     *                                 retrieve the info about that target.
     * @return stdClass containing the user info.
     */
    public function info($usernameOrId = null)
    {
        $user = null;

        // Get the user ID (and the info if a request is made to Twitch)
        if (is_numeric($usernameOrId)) {
            $userId = (int) $usernameOrId;
        } elseif (!is_null($usernameOrId)) {
            // Fetch the data from Twitch and get the user info by the way
            if (!isset(self::$userIdCache[$usernameOrId])) {
                $userId = $this->getUserId($usernameOrId, $user);
                self::$userIdCache[$usernameOrId] = $userId;
            } else { // Fetch the data from the cache
                $userId = self::$userIdCache[$usernameOrId];
            }
        }
        
        if (is_null($user)) {
            $user = $this->kraken->query(Client::QUERY_TYPE_GET, (!empty($userId) ? "/users/$userId" : "/user"));
        }
        
        return $user;
    }

    /**
     * Gets the ID of an user. This is a pretty low-level method, it will execute the query right away every time
     * and return the user ID (along with the other user info with the optional reference).
     * 
     * Using the info() method with the username as a string is advised over this method since info() has some caching
     * mechanics that are made to provide the requested info using the least amount of requests. Use this method only
     * if you just need the userId and nothing else.
     * 
     * @param string $username The username to get the user ID of.
     * @param array &$userInfo A reference to put the user info in when returned by the API. Optional.
     * 
     * @return int The user ID for the requested user, or null if the user is not found.
     */
    public function getUserId($username, &$userInfo = null)
    {
        $response = $this->kraken->query(Client::QUERY_TYPE_GET, "/users", ['login' => $username]);
        
        // Return null if no users are found
        if(empty($response) || $response->_total == 0) {
            return null;
        }
            
        $userInfo = reset($response->users);
        return $userInfo->_id;
    }
}