<?php
	namespace CHAPI;

	use PhpAmqpLib\Connection\AMQPStreamConnection;
	use PhpAmqpLib\Message\AMQPMessage;

	class MessageQ {

		public $connection;
		public $channel;
		public $queue;

		static function newInstance($queue = 'base_queue') {
			$new = new self();

			$new->queue = $queue;
			$new->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
			$new->channel = $new->connection->channel();
			$new->channel->queue_declare($new->queue, false, true, false, false);

			return $new;
		}

		function sendMessage($data) {

			$msg = new AMQPMessage(
				$data,
				[ 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT ]
			);

			$this->channel->basic_publish($msg, '', $this->queue);
			return true;
		}

		function close() {

			$this->channel->close();
			$this->connection->close();
			return true;
		}
	}
?>