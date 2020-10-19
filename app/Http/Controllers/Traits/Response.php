<?php

namespace App\Http\Controllers\Traits;

trait Response {

    public function sendResponse($result, $message)
    {
        $response = [
            'success'   => true,
            'data'      => $result,
            'message'   => $message,
        ];
        return response()->json($response,200);
    }

    public function sendError($error, $errorMessage = [], $code = 402)
    {
        $respon = [
            'success'   => false ,
            'data'      => '' ,
            'message'   => $error ,
        ];

        if(!empty($errorMessage)){
            $respon['data'] = $errorMessage;
        }
        return response()->json($respon, $code);
    }
}
