<?php


namespace Ecotone\Enqueue;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\Logger\ExceptionLoggingInterceptorBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class AmqpBackendMessageChannelConsumer
 * @package Ecotone\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnqueueBackendMessageChannelConsumer implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof EnqueueMessageChannelBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();

        $pollingConsumerBuilder->addAroundInterceptor(EnqueueAcknowledgeConfirmationInterceptor::createAroundInterceptor($messageHandlerBuilder->getEndpointId()));
        $pollingConsumerBuilder->addAroundInterceptor(AroundInterceptorReference::createWithObjectBuilder(
            "errorLog",
            new ExceptionLoggingInterceptorBuilder(),
            "logException",
            ErrorChannelInterceptor::PRECEDENCE - 1,
            ""
        ));

        return $this->buildConsumerFrom($channelResolver, $referenceSearchService, $messageHandlerBuilder, $pollingMetadata, $pollingConsumerBuilder);
    }

    protected function buildConsumerFrom(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata, PollingConsumerBuilder $pollingConsumerBuilder): ConsumerLifecycle
    {
        return $pollingConsumerBuilder->build($channelResolver, $referenceSearchService, $messageHandlerBuilder, $pollingMetadata);
    }
}