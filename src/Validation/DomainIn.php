<?php namespace Hampel\Tlds\Validation;

use Closure;

class DomainIn extends ValidationBase
{
    /** @var array */
    protected $tlds;

    /**
     * @param array $tlds array of valid TLDs
     */
    public function __construct(array $tlds)
    {
        $this->tlds = $tlds;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->validator->isDomain($value, $this->tlds))
        {
            $fail($attribute, 'tlds::validation.domain_in')
                ->translate([
                    'attribute' => $attribute,
                    'values' => implode(', ', $this->tlds)
                ]);
        }
    }
}
