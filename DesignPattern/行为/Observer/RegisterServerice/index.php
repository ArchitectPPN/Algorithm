<?php

require_once("Service\Promotion.php");
require_once("Service\SendMsg.php");
require_once("Service\UserController.php");
require_once("Observer\RegObserve.php");
require_once("Observer\RegSubject.php");
require_once("Observer\Imp\RegPromotionObserver.php");
require_once("Observer\Imp\RegSendMsgObserver.php");
require_once("Observer\Imp\RegConcreteObserver.php");

$userController = new \Service\UserController();
$userController->registerUser();