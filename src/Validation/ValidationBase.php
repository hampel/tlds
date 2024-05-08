<?php namespace Hampel\Tlds\Validation;

use Hampel\Validate\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;

abstract class ValidationBase implements ValidationRule
{
    /** @var Validator */
    protected $validator;

    public function __construct()
    {
        $this->validator = App::make(Validator::class);
    }
}
