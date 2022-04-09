<?php


namespace Observer\Imp;

use Observer\RegObserve;
use Service\Promotion;

class RegPromotionObserver implements RegObserve
{
    /**
     * @param $userId
     */
    public function handleRegSuccess($userId)
    {
        (new Promotion())->issueNewUserExperienceCash($userId);
    }
}