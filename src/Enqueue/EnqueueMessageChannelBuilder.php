<?php

namespace Ecotone\Enqueue;

use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;

abstract class EnqueueMessageChannelBuilder implements MessageChannelBuilder
{
    public function isPollable(): bool
    {
        return true;
    }

    public abstract function prepareProviderChannel(PollingMetadata $pollingMetadata) : MessageChannel;

    public abstract function getDefaultConversionMediaType(): ?MediaType;

    public abstract function withDefaultConversionMediaType(string $mediaType): self;

    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        /** @var ApplicationConfiguration|null $applicationConfiguration */
        $applicationConfiguration = $referenceSearchService->has(ApplicationConfiguration::class) ? $referenceSearchService->get(ApplicationConfiguration::class) : null;
        $pollingMetadata = PollingMetadata::create("");

        if (!$this->getDefaultConversionMediaType() && $applicationConfiguration && $applicationConfiguration->getDefaultSerializationMediaType()) {
            $this->withDefaultConversionMediaType($applicationConfiguration->getDefaultSerializationMediaType());
        }

        if ($applicationConfiguration && $applicationConfiguration->getDefaultErrorChannel()) {
            $pollingMetadata = $pollingMetadata
                ->setErrorChannelName($applicationConfiguration->getDefaultErrorChannel());
        }
        if ($applicationConfiguration && $applicationConfiguration->getDefaultMemoryLimitInMegabytes()) {
            $pollingMetadata = $pollingMetadata
                ->setMemoryLimitInMegaBytes($applicationConfiguration->getDefaultMemoryLimitInMegabytes());
        }
        if ($applicationConfiguration && $applicationConfiguration->getChannelPollRetryTemplate()) {
            $pollingMetadata = $pollingMetadata
                ->setChannelPollRetryTemplate($applicationConfiguration->getChannelPollRetryTemplate());
        }

        return $this->prepareProviderChannel($pollingMetadata);
    }
}