<?php

return [
    'allow_data' => filter_var(env('ALLOW_DEMO_DATA', false), FILTER_VALIDATE_BOOL),
];
