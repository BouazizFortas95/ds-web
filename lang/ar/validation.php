<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن تكون قيمة :attribute نصًا.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صالحًا.',
    'max' => [
        'string' => 'يجب ألا يزيد طول :attribute على :max حرفًا.',
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
    ],
    'min' => [
        'string' => 'يجب ألا يقل طول :attribute عن :min أحرف.',
        'numeric' => 'يجب ألا تكون قيمة :attribute أقل من :min.',
    ],
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'unique' => 'قيمة :attribute مستخدمة مسبقًا.',

    'attributes' => [
        'name' => 'الاسم الكامل',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
    ],
];
