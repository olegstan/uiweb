<?
namespace core\oauth2\client;

use core\oauth2\OAuth2;
use core\oauth2\Token;

class VKOAuth2 extends OAuth2
{
    public $params = [
        'client_id' => '4764214',
        'client_secret' => 'OUNi0k7me7HwpthXgWxI'
    ];

    public function __construct()
    {

    }

    public function urlAuthorize()
    {
        return 'https://oauth.vk.com/authorize';
    }

    public function urlAccessToken()
    {
        return 'https://api.vk.com/oauth/access_token';
    }

    public function urlUserDetails(Token $token)
    {
        $fields = [
            'nickname',
            'screen_name',
            'sex',
            'bdate',
            'city',
            'country',
            'timezone',
            'photo_50',
            'photo_100',
            'photo_200_orig',
            'has_mobile',
            'contacts',
            'education',
            'online',
            'counters',
            'relation',
            'last_seen',
            'status',
            'can_write_private_message',
            'can_see_all_posts',
            'can_see_audio',
            'can_post',
            'universities',
            'schools',
            'verified',
        ];
        return 'https://api.vk.com/method/users.get?user_id=' . $token->uid . '&fields=' . implode(',', $fields) . '&access_token=' . $token . '';
    }
}