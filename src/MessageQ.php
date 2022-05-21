<?php
	namespace CHAPI;

	use Exception;
	use PhpAmqpLib\Connection\AMQPStreamConnection;
	use PhpAmqpLib\Message\AMQPMessage;

	class MessageQ {

		/**
		 * @var AMQPStreamConnection
		 */
		public AMQPStreamConnection $connection;
		/**
		 * @var
		 */
		public $channel;
		/**
		 * @var string
		 */
		public string $queue;

		/**
		 * @param string $queue
		 * @return MessageQ
		 */
		static function newInstance(string $queue = 'base_queue'): MessageQ {
			$new = new self();

			$new->queue = $queue;
			$new->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
			$new->channel = $new->connection->channel();
			$new->channel->queue_declare($new->queue, false, true, false, false);

			return $new;
		}

		/**
		 * @param $data
		 * @return bool
		 */
		function sendMessage($data): bool {

			$msg = new AMQPMessage(
				$data,
				[ 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT ]
			);

			$this->channel->basic_publish($msg, '', $this->queue);
			return true;
		}

		/**
		 * @return bool
		 * @throws Exception
		 */
		function close(): bool {

			$this->channel->close();
			$this->connection->close();
			return true;
		}
	}