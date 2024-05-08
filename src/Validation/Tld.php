<?php namespace Hampel\Tlds\Validation;

use Closure;
use Hampel\Tlds\Facades\Tlds;

class Tld extends ValidationBase
{
    /**
     * @inheritDoc
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->validator->isTld($value, Tlds::get()))
        {
            $fail($attribute, 'tlds::validation.tld')
                ->translate(['attribute' => $attribute]);
        }
    }
}
