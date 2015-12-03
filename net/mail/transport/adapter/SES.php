<?php
namespace li3_mailer\net\mail\transport\adapter;

require_once __DIR__ . '/aws.phar';

use Aws\Common\Credentials\Credentials;
use Aws\Ses\SesClient;
use Aws\Ses\Exception\MessageRejectedException;

use RuntimeException;

class SES extends \li3_mailer\net\mail\Transport
{
	public function deliver($message, array $options = array())
	{
		$credentials = new Credentials(
			$message->access_key_id,
			$message->access_key_secret
		);

		$client = SesClient::factory(
			array(
				'credentials' => $credentials,
				'region' => 'eu-west-1'
			)
		);

		$body = array();

		if ($text = $message->body('text')) {
			$body['Text'] = array(
				'Data' => $text,
				'Charset' => $message->charset
			);
		}

		if ($html = $message->body('html')) {
			$body['Html'] = array(
				'Data' => $html,
				'Charset' => $message->charset
			);
		}

		$data = array(
			'Source' => $message->from,
			'Destination' => array(
				'ToAddresses' => array($message->to),
				),
			'Message' => array(
				'Subject' => array(
					'Data' => $message->subject,
					'Charset' => $message->charset
					),
				'Body' => $body
				)
			);

		try {
			$client->sendEmail(
				$data
			);
		} catch (\MessageRejectedException $e) {
			error_log($e->getMessage());
			return 'rejected';
		} catch (\Exception $e) {
			error_log($e->getMessage());
			return false;
		}

		return 'delivered';
	}
}