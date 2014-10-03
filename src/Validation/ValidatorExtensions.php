<?php namespace Hampel\Tlds\Validation;

use Hampel\Tlds\Tlds;
use Hampel\Validate\Validator;

class ValidatorExtensions
{
	/**
	 * @var \Hampel\Validate\Validator
	 */
	private $validator;

	/**
	 * @var \Hampel\Tlds\Tlds
	 */
	private $tlds;

	public function __construct(Validator $validator, Tlds $tlds)
	{
		$this->validator = $validator;
		$this->tlds = $tlds;
	}

	public function validateDomain($attribute, $value, $parameters)
	{
		return $this->validator->isDomain($value, $this->tlds->get());
	}

	public function validateDomainIn($attribute, $value, $parameters)
	{
		return $this->validator->isDomain($value, $parameters);
	}

	public function replaceDomainIn($message, $attribute, $rule, $parameters)
	{
		return str_replace(':values', implode(', ', $parameters), $message);
	}

	public function validateTld($attribute, $value, $parameters)
	{
		return $this->validator->isTld($value, $this->tlds->get());
	}

	public function validateTldIn($attribute, $value, $parameters)
	{
		return $this->validator->isTld($value, $parameters);
	}

	public function replaceTldIn($message, $attribute, $rule, $parameters)
	{
		return str_replace(':values', implode(', ', $parameters), $message);
	}
}

?>
