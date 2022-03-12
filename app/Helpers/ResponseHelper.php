<?php

namespace App\Helpers;

class ResponseHelper
{
    public function generateRespond($data,$respondCode)
    {
        if ($respondCode === 200) {
            $respond = [
                "success" => true,
                "message"=> "Success",
                "data" => $data
            ];
            return $respond;
        }elseif ($respondCode === 404){
            $respond = [
                "success" => false,
                "message"=> "Not Found"
            ];
            return $respond;
        }else{
            $respond = [
                "success" => false,
                "message"=> $data
            ];
            return $respond;
        }
        
    }
}