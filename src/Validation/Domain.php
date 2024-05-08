<?php namespace Hampel\Tlds\Validation;

use Closure;
use Hampel\Tlds\Facades\Tlds;

class Domain extends ValidationBase
{
    /**
     * @inheritDoc
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->validator->isDomain($value, Tlds::get()))
        {
            $fail($attribute, 'tlds::validation.domain')
                ->translate(['attribute' => $attribute]);
        }
    }
}
