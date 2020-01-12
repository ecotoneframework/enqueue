<?php
declare(strict_types=1);

namespace Ecotone\Enqueue;

use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpBinding;
use Ecotone\Amqp\AmqpExchange;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Enqueue\EnqueueAcknowledgeConfirmationInterceptor;
use Ecotone\Enqueue\EnqueueBackendMessageChannelConsumer;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Class AmqpModule
 * @package Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class EnqueueModule implements AnnotationModule
{
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "enqueueModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        /** @var ApplicationConfiguration $applicationConfiguration */
        $applicationConfiguration = null;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ApplicationConfiguration) {
                $applicationConfiguration = $extensionObject;
                break;
            }
        }

        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof EnqueueMessageChannelBuilder && !$extensionObject->getDefaultConversionMediaType()) {
                $extensionObject->withDefaultSerializationMediaType($applicationConfiguration->getDefaultSerializationMediaType());
            }
        }

        $configuration->registerRelatedInterfaces([InterfaceToCall::create(EnqueueAcknowledgeConfirmationInterceptor::class, "ack")]);
        $configuration->registerConsumerFactory(new EnqueueBackendMessageChannelConsumer());
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof ApplicationConfiguration
            || $extensionObject instanceof EnqueueMessageChannelBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}