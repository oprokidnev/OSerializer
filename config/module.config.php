<?php

namespace OSerializer;

return [
    'o-serializer' => include __DIR__ . '/serializer.config.php',
    'service_manager' => [
        'invokables' => [
            Serializer\Strategy\JsonNamingStrategy::class => Serializer\Strategy\JsonNamingStrategy::class,
            Serializer\Formatter\CircularFormatter::class => Serializer\Formatter\CircularFormatter::class,
            Serializer\Formatter\DateTimeFormatter::class => Serializer\Formatter\DateTimeFormatter::class,
            Serializer\Formatter\GroupFormatter::class => Serializer\Formatter\GroupFormatter::class,
            Serializer\Formatter\EmbeddedFormatter::class => Serializer\Formatter\EmbeddedFormatter::class,
            Serializer\Formatter\NumberFormatter::class => Serializer\Formatter\NumberFormatter::class,
            Serializer\Transformer\AnnotationTransformer::class => Serializer\Transformer\AnnotationTransformer::class,
            Serializer\Filter\AnnotationFilter::class => Serializer\Filter\AnnotationFilter::class,
        ],
        'factories' => [
            'OSerializer\\Serializer\\DoctrineORM' => 'OSerializer\\Serializer\\DoctrineObjectSerializerFactory',
        ],
        'aliases' => [
            'DoctrineORMSerializer' => 'OSerializer\\Serializer\\DoctrineORM',
        /**
         * @todo
         */
//            'DoctrineODMSerializer' => 'OSerializer\\Serializer\\DoctrineORM'
        ],
        'shared' => [
            'OSerializer\\Serializer\\DoctrineORM' => true,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            Controller\Plugin\Serialize::class => Controller\Plugin\Serialize::class,
        ],
        'aliases' => [
            'serialize' => Controller\Plugin\Serialize::class,
        ],
    ],
    /**
     * Zend apigility support
     */
    'hydrators' => [
        'factories' => [
            'OSerializer\\Serializer\\DoctrineORM' => 'OSerializer\\Serializer\\DoctrineObjectSerializerFactory',
//            'OSerializer\\Serializer\\DoctrineODM' => 'OSerializer\\Serializer\\DoctrineObjectSerializerODMFactory',
        ],
    ],
];
