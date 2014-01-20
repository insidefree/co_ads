<?php

namespace GoogleAPI\OAuth2Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GoogleAPIOAuth2Bundle extends Bundle
{
}

// todo: put this somewhere else
require_once 'GoogleAPIPHPClient/Google_Client.php';
require_once 'GoogleAPIPHPClient/contrib/Google_AdsensehostService.php';