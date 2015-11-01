<?php

return [
    'serializer' => [
        'naming_strategy' => \OSerializer\Serializer\Strategy\JsonNamingStrategy::class,
        'formatters' => [
            \OSerializer\Serializer\Formatter\CircularFormatter::class,
            \OSerializer\Serializer\Formatter\DateTimeFormatter::class,
            \OSerializer\Serializer\Formatter\GroupFormatter::class,
            \OSerializer\Serializer\Formatter\EmbeddedFormatter::class,
        ],
        'transformers' => [
            \OSerializer\Serializer\Transformer\AnnotationTransformer::class,
        ],
        'filters' => [
            \OSerializer\Serializer\Filter\AnnotationFilter::class,
        ],
    ],
];
