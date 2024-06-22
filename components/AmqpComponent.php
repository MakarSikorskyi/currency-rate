<?php

namespace app\components;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\Component;

class AmqpComponent extends Component
{
    public $host;
    public $port;
    public $user;
    public $password;
    public $queue;

    private $connection;
    private $channel;

    public function init()
    {
        parent::init();
        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, false, false, false);
    }

    public function sendMessage($message)
    {
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, '', $this->queue);
    }

    public function consume($callback)
    {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
