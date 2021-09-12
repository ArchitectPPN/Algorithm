<?php

namespace DesignPattern;

class GetUserInfoService extends BaseObject
{
    /**
     * 获取医生信息
     *
     * @param string $doctorCode
     * @return array
     */
    private function getDoctorInfo(string $doctorCode): array
    {
        return [
            'name' => 'Doc.Xiao',
            'age' => 21,
            'sex' => 'female'
        ];
    }

    /**
     * 获取医生信息
     *
     * @param array $requestParams
     * @return array[]
     */
    protected function business(array $requestParams): array
    {
        return [
            'doctor_info' => $this->getDoctorInfo($requestParams['doctor_code'])
        ];
    }
}