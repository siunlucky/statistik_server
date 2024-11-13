<?php

namespace App\Classes\ApiResponse\SuccessResponse;

use App\Classes\ApiResponse\BaseSuccessApiResponseClass;

class CreatedResponse extends BaseSuccessApiResponseClass {
    public function __construct($data = null, $recordsTotal = 0)
    {
        parent::__construct(201, 'CREATED', $data, $recordsTotal);
    }
}
