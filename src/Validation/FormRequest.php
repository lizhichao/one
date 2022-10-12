<?php 

namespace One\Validation;

use One\Http\Response;
use Rakit\Validation\Validator;

class FormRequest
{
	/**
	 * @var Response
	 */
	public $response = null;

	/**
	 * @var \Rakit\Validation\Validator
	 */
	private $validator;

	/**
	 * @var \Rakit\Validation\Validation
	 */
	private $validation;

	public function __construct(Response $response)
	{
		$this->response = $response;
		$this
		->setValidator()
		->makeValidate()
		->checkFailsValidator();
	}

	public function validationData(): array
	{
		return [];
	}

	public function rules(): array
	{
		return [];
	}

	public function messages(): array
	{
		return [];
	}

	public function attributes(): array
	{
		return [];
	}

	public function failedValidation(\Rakit\Validation\Validation $validation)
	{
		throw new ValidationException(
			response: $this->response,
			errors: $this->getErrors(exections: $validation->errors()->toArray()),
			code: 422
		);

	}

	public function validated(): array
	{
		return $this->validation->getValidatedData();
	}

	private function setValidator(): self
	{

		$this->validator = new Validator(
			messages: __('validation')
		);

		return $this;
	}

	private function makeValidate(): self
	{
		$this->validation = $this->validator->make(
			inputs: $this->validationData(),
			rules: $this->rules(),
			messages: $this->messages()
		)->setAliases(
			aliases: $this->attributes()
		);

		return $this;
	}

	private function checkFailsValidator(): void
	{
		$this->validation->validate();
		if ($this->validation->fails()) {
			$this->failedValidation(validation: $this->validation);
		}
	}

	private function getErrors(array $exections): array
	{
		$errors = [];
		foreach ($exections as $key => $e) {
			$errors[$key] = array_values($e);
		}

		return $errors;
	}

}