<?php

namespace App\Classes\ApiResponse;

use Illuminate\Http\JsonResponse;

class BaseSuccessApiResponseClass {
    protected $code;
    protected $status;
    protected $data;
    protected $recordsTotal;

    public function __construct($code = 200, $status = 'SUCCESS', $data = null, $recordsTotal = 0)
    {
        $this->code = $code;  // HTTP success code
        $this->status = $status;
        $this->data = $data;
        $this->recordsTotal = $recordsTotal;
    }

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'code' => $this->code,
            'status' => $this->status,
            'recordsTotal' => $this->recordsTotal,
            'data' => $this->data,
            'error' => null,
        ], $this->code);
    }
}


