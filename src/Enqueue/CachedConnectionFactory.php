<?php


namespace Ecotone\Enqueue;


use Interop\Queue\ConnectionFactory;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Producer;

class CachedConnectionFactory implements ConnectionFactory
{
    private static $instances = [];

    /**
     * @var ReconnectableConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var null|Context
     */
    private $cachedContext = null;
    /**
     * @var Consumer[]
     */
    private $cachedConsumer = [];
    /**
     * @var Producer[]
     */
    private $cachedProducers;

    private function __construct(ReconnectableConnectionFactory $reconnectableConnectionFactory)
    {
        $this->connectionFactory = $reconnectableConnectionFactory;
    }

    public static function createFor(ReconnectableConnectionFactory $reconnectableConnectionFactory)
    {
        if (!isset(self::$instances[$reconnectableConnectionFactory->getConnectionInstanceId()])) {
            self::$instances[$reconnectableConnectionFactory->getConnectionInstanceId()] = new self($reconnectableConnectionFactory);
        }

        return self::$instances[$reconnectableConnectionFactory->getConnectionInstanceId()];
    }

    public function createContext(): Context
    {
        if (!$this->cachedContext || $this->connectionFactory->isDisconnected($this->cachedContext)) {
            if ($this->connectionFactory->isDisconnected($this->cachedContext)) {
                $this->connectionFactory->reconnect();
            }

            $this->cachedContext = $this->connectionFactory->createContext();
        }

        echo "\n cached\n";
        var_dump(spl_object_id($this->cachedContext->getLibChannel()));
        echo "====\n";

        return $this->cachedContext;
    }

    public function getConsumer(Destination $destination, ?string $consumerId = null) : Consumer
    {
        $consumerId = $consumerId ? $consumerId : "";
        if (!isset($this->cachedConsumer[$consumerId]) || $this->connectionFactory->isDisconnected($this->cachedContext)) {
            $this->cachedConsumer[$consumerId] = $this->createContext()->createConsumer($destination);
        }

        return $this->cachedConsumer[$consumerId];
    }

    public function getProducer(?string $producerId = null) : Producer
    {
        $producerId = $producerId ? $producerId : "";
        if (!isset($this->cachedProducers[$producerId]) || $this->connectionFactory->isDisconnected($this->cachedContext)) {
            $this->cachedProducers[$producerId] = $this->createContext()->createProducer();
        }

        return $this->cachedProducers[$producerId];
    }
}