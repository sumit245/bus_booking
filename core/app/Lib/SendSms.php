<?php

namespace App\Lib;

use Illuminate\Support\Facades\Http;
use Textmagic\Services\TextmagicRestClient;
use Twilio\Rest\Client;


class SendSms
{

	public function clickatell($to,$fromName,$message,$credentials)
	{
		$message = urlencode($message);
		@file_get_contents("https://platform.clickatell.com/messages/http/send?apiKey=$credentials->clickatell_api_key&to=$to&content=$message");
	}

	public function infobip($to,$fromName,$message,$credentials){
		$message = urlencode($message);
		$response = Http::withHeaders([
            'Authorization' => 'App 5b63a9665af194b7570f6ceeb20e3a85-31fd30cb-4927-41ea-b3a8-c6951f15cc12',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://api.infobip.com/sms/2/text/advanced', [
            'messages' => [
                [
                    'from' => $fromName,
                    'destinations' => [
                        ['to' => $to],
                    ],
                    'text' => $message,
                ],
            ],
        ]);
        // Optional: handle response
        if ($response->successful()) {
            return $response->body(); // Message sent successfully
        } else {
            return $response->status(); // Handle error response
        }

	}

	public function messageBird($to,$fromName,$message,$credentials){
		$MessageBird = new \MessageBird\Client($credentials->message_bird_api_key);
	  	$Message = new \MessageBird\Objects\Message();
	  	$Message->originator = $fromName;
	  	$Message->recipients = array($to);
	  	$Message->body = $message;
	  	$MessageBird->messages->create($Message);
	}

	public function nexmo($to,$fromName = 'admin',$message,$credentials){
		$basic  = new \Vonage\Client\Credentials\Basic($credentials->nexmo_api_key, $credentials->nexmo_api_secret);
		$client = new \Vonage\Client($basic);
		$response = $client->sms()->send(
		    new \Vonage\SMS\Message\SMS($to, $fromName, $message)
		);
		$message = $response->current();
	}

	public function smsBroadcast($to,$fromName,$message,$credentials){
		$message = urlencode($message);
		$response = @file_get_contents("https://api.smsbroadcast.com.au/api-adv.php?username=$credentials->sms_broadcast_username&password=$credentials->sms_broadcast_password&to=$to&from=$fromName&message=$message&ref=112233&maxsplit=5&delay=15");
	}

	public function twilio($to,$fromName,$message,$credentials){
		$account_sid = $credentials->account_sid;
		$auth_token = $credentials->auth_token;
		$twilio_number = $credentials->from;

		$client = new Client($account_sid, $auth_token);
		$client->messages->create(
		    '+'.$to,
		    array(
		        'from' => $twilio_number,
		        'body' => $message
		    )
		);
	}

	public function textMagic($to,$fromName,$message,$credentials){
		$client = new TextmagicRestClient($credentials->text_magic_username, $credentials->apiv2_key);
	    $result = $client->messages->create(
	        array(
	            'text' => $message,
	            'phones' => $to
	        )
	    );
	}

}