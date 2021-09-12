<?php

namespace DesignPattern;

class Application
{
    public function run()
    {
        var_dump((new GetUserInfoService())->run(['doctor_code' => 'DC10020210808'], 'get_doctor_info'));
    }
}


