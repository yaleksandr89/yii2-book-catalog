<?php

return [
    'bookImageStorageRoot' => '@app/web/uploads/books',
    'smsPilotApiKey' => getenv('SMSPILOT_API_KEY') ?: '',
];
