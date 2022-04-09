<?php


namespace Observer\Imp;

use Observer\RegObserve;
use Service\SendMsg;

class RegSendMsgObserver implements RegObserve
{
    /**
     * @param $userId
     */
    public function handleRegSuccess($userId)
    {
        (new SendMsg())->regNewUserSendMsg($userId);
    }
}