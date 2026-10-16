<?php
return [
    'api_key' => getenv('OPENAI_API_KEY'),
    'model'   => 'gpt-4o-mini',
    'temperature' => 0.4,
    'timeout' => 20
];