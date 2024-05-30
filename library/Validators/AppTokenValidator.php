<?php

namespace packages\userpanel_oauth\Validators;

use packages\base\InputValidationException;
use packages\base\Validator\IValidator;
use packages\base\Validator\NullValue;
use packages\userpanel_oauth\App;

class AppTokenValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return [];
    }

    /**
     * Validate data to be a App token.
     *
     * @return App|NullValue
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data)
    {
        if (!$data) {
            if (!isset($rule['empty']) or !$rule['empty']) {
                throw new InputValidationException($input, 'empty-value');
            }
            if (isset($rule['default'])) {
                return $rule['default'];
            }

            return new NullValue();
        }
        if (!is_string($data)) {
            throw new InputValidationException($input);
        }
        $status = $data['status'] ?? [App::ACTIVE];
        $model = App::where('token', $data);
        if ($status) {
            $model->where('status', $status, 'IN');
        }
        $app = $model->getOne();
        if (!$app) {
            throw new InputValidationException($input);
        }

        return $app;
    }
}
