<?php

declare(strict_types=1);

return [
    'bookImageStorageRoot' => '@app/web/uploads/books',
    'smsPilotApiKey' => getenv('SMSPILOT_API_KEY') ?: '',
];
