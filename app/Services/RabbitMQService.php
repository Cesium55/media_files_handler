<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    protected $connection;
    protected $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('brokers.host', '127.0.0.1'),
            config('brokers.port', 5672),
            config('brokers.user', 'guest'),
            config('brokers.password', 'guest')
        );

        $this->channel = $this->connection->channel();
    }

    public function publish(string $queue, array $data): void
    {
        $this->channel->queue_declare($queue, false, false, false, false);

        $msg = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE));

        $this->channel->basic_publish($msg, '', $queue);
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
