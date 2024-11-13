<?php

namespace App\Classes\ApiResponse\SuccessResponse;

use App\Classes\ApiResponse\BaseSuccessApiResponseClass;

class OKResponse extends BaseSuccessApiResponseClass {
    public function __construct($data = null, $recordsTotal = 0)
    {
        parent::__construct(200, 'SUCCESS', $data, $recordsTotal);
    }
}
