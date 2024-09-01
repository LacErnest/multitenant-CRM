<?php


namespace App\Services\Xero\Entities;

class BaseEntity
{
    public function getRandNum()
    {
        $randNum = strval(rand(1000, 100000));
        return $randNum;
    }
}
