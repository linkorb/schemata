<?php

namespace Schemata\Validators;

use Symfony\Component\Validator\Constraint;

class CamelCaseLower extends Constraint
{
    public $message = 'The string "{{ string }}" should be lowerCamelCase';
}
