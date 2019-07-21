<?php

namespace Schemata\Validators;

use Symfony\Component\Validator\Constraint;

class CamelCaseUpper extends Constraint
{
    public $message = 'The string "{{ string }}" should be UpperCamelCase';
}
