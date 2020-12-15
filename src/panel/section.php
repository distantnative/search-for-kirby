<?php

return [
    'props' => [
        'headline' => function ($headline = null) {
            if ($headline === null) {
                return t('search');
            }

            return $headline;
        }
    ]
];
