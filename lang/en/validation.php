<?php

/*
| Only the rules this app actually enforces are translated. Laravel falls back
| to its bundled English file for anything missing, so adding a rule here later
| is an optimisation rather than a requirement.
*/

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'email' => 'The :attribute must be a valid email address.',
    'max' => [
        'string' => 'The :attribute must not be longer than :max characters.',
        'numeric' => 'The :attribute must not be greater than :max.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',
    'unique' => 'The :attribute has already been taken.',

    'attributes' => [
        'name' => 'full name',
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
    ],
];
