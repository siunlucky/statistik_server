<?php

namespace App\Classes\ApiResponse\ErrorResponse;

use App\Classes\ApiResponse\BaseErrorApiResponseClass;

class BadRequestErrorResponse extends BaseErrorApiResponseClass
{
    public function __construct($message = 'Bad request')
    {
        parent::__construct(400, 'BAD_REQUEST', $message);
    }
}
