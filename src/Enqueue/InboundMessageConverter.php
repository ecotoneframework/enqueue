<?php


namespace Ecotone\Enqueue;

use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Interop\Queue\Consumer as EnqueueConsumer;
use Interop\Queue\Message as EnqueueMessage;

class InboundMessageConverter
{
    /**
     * @var string
     */
    private $acknowledgeMode;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var string
     */
    private $acknowledgeHeaderName;

    public function __construct(string $acknowledgeMode, string $acknowledgeHeaderName, HeaderMapper $headerMapper)
    {
        $this->acknowledgeMode = $acknowledgeMode;
        $this->headerMapper = $headerMapper;
        $this->acknowledgeHeaderName = $acknowledgeHeaderName;
    }

    public function toMessage(EnqueueMessage $source, EnqueueConsumer $consumer): MessageBuilder
    {
        $messageBuilder = MessageBuilder::withPayload($source->getBody())
            ->setMultipleHeaders($this->headerMapper->mapToMessageHeaders($source->getProperties()));

        if (in_array($this->acknowledgeMode, [EnqueueAcknowledgementCallback::AUTO_ACK, EnqueueAcknowledgementCallback::MANUAL_ACK])) {
            if ($this->acknowledgeMode == EnqueueAcknowledgementCallback::AUTO_ACK) {
                $amqpAcknowledgeCallback = EnqueueAcknowledgementCallback::createWithAutoAck($consumer, $source);
            } else {
                $amqpAcknowledgeCallback = EnqueueAcknowledgementCallback::createWithManualAck($consumer, $source);
            }

            $messageBuilder = $messageBuilder
                ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, $this->acknowledgeHeaderName)
                ->setHeader($this->acknowledgeHeaderName, $amqpAcknowledgeCallback);
        }

        return $messageBuilder;
    }
}