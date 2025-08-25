<?php

namespace App\Http\Utility;

use Exception;
use App\Models\User;
use App\Models\Admin;
use GuzzleHttp\Client;
use App\Models\Gateway;
use App\Models\Template;
use App\Enums\SettingKey;
use App\Enums\StatusEnum;
use App\Traits\Manageable;
use Illuminate\Support\Arr;
use App\Models\DispatchLog;
use App\Enums\Common\Status;
use App\Traits\Dispatchable;
use App\Enums\EmailProviderkey;
use App\Enums\DefaultTemplateSlug;
use App\Enums\System\ChannelTypeEnum;
use App\Services\System\TemplateService;

# SMTP
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

# SendGrid
use SendGrid;
use SendGrid\Mail\Mail;

# AWS
use Aws\Ses\SesClient;
use ErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

# MailGun
use Mailgun\Mailgun;
use Symfony\Component\Mailer\Exception\TransportException;

class SendMail
{
    use Dispatchable, Manageable;

    protected $templateService;

    public function __construct()
    {
        $this->templateService = new TemplateService();
    }

    /**
     * MailNotification
     *
     * @param Gateway $gateway
     * @param Template $template
     * @param Admin|User|Model $user
     * @param array|null $mailCode
     * 
     * @return bool
     */
    public function MailNotification(Gateway $gateway, Template $template, Admin|User|Model $user, array|null $mailCode = null): bool
    {
        $globalTemplate = $this->getSpecificLogByColumn(
            model: new Template(),
            column: 'slug',
            value: DefaultTemplateSlug::GLOBAL_TEMPLATE->value,
            attributes: [
                "user_id" => null,
                "channel" => ChannelTypeEnum::EMAIL,
                "global" => true,
                "default" => false,
                "status" => Status::ACTIVE->value
            ]
        );

        $messageBody = $this->templateService->processTemplate(template: $template, variables: $mailCode);
        
        $globalMailCode = [
            "name" => site_settings(SettingKey::SITE_NAME->value, "Xsender"),
            "message" => $messageBody
        ];
        $finalMessage = $this->templateService->processTemplate(template: $globalTemplate, variables: $globalMailCode);
        
        if (site_settings('email_notifications') == StatusEnum::TRUE->status() || site_settings('email_notifications') == Status::ACTIVE->value) {
            return $this->sendWithHandler($gateway, $user->email, Arr::get($template->template_data, "subject"), $finalMessage);
        }
        return false;
    }

    /**
     * send
     *
     * @param Gateway $gateway
     * @param array|string $to
     * @param array|string|null $subject
     * @param array|string|null $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    public function send(Gateway $gateway, array|string $to, array|string|null $subject = null, array|string|null $mailBody = null, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        return $this->sendWithHandler($gateway, $to, $subject, $mailBody, $dispatchLog);
    }

    /**
     * sendWithHandler
     *
     * @param Gateway $gateway
     * @param array|string $to
     * @param array|string|null $subject
     * @param array|string|null $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    protected function sendWithHandler(Gateway $gateway, array|string $to, array|string|null $subject = null, array|string|null $mailBody = null, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        $creds = $this->getCredentials(ChannelTypeEnum::EMAIL, $gateway->type, $gateway);
        if (!$creds) {
            if ($dispatchLog) $this->fail($dispatchLog, translate("Gateway credentials are not available"));
            return false;
        }
    
        try {
            $timeout = 200; 
    
            $success = false;
            $providerMethod = match ($gateway->type) {
                EmailProviderkey::SMTP->value => fn () => $this->sendViaSmtp($gateway, $creds, $to, $subject, $mailBody, $dispatchLog),
                EmailProviderkey::SENDGRID->value => fn () => $this->sendViaSendgrid($gateway, $creds, $to, $subject, $mailBody, $dispatchLog),
                EmailProviderkey::AWS->value => fn () => $this->sendViaAws($gateway, $creds, $to, $subject, $mailBody, $dispatchLog),
                EmailProviderkey::MAILJET->value => fn () => $this->sendViaMailjet($gateway, $creds, $to, $subject, $mailBody, $dispatchLog),
                EmailProviderkey::MAILGUN->value => fn () => $this->sendViaMailgun($gateway, $creds, $to, $subject, $mailBody, $dispatchLog),
                default => throw new \Exception("Unknown gateway type: {$gateway->type}"),
            };
    
            $result = null;
            set_time_limit($timeout);
            try {
                $result = $providerMethod();
                set_time_limit(0); 
            } catch (ErrorException $e) {
                if (str_contains($e->getMessage(), 'Maximum execution time')) {
                    throw new Exception("Provider timed out after {$timeout} seconds");
                }
                throw $e;
            }
    
            $success = $result;
    
            if ($success && $dispatchLog) {
                $this->markAsDelivered($dispatchLog);
            }
            return $success;
        } catch (Exception $e) {
            
            if($dispatchLog) {

                $this->fail($dispatchLog, $e->getMessage());
            }
            return false;
        }
    }

    /**
     * sendViaSmtp
     *
     * @param Gateway $gateway
     * @param array $creds
     * @param string $to
     * @param array|string $subject
     * @param array|string $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    private function sendViaSmtp(Gateway $gateway, array $creds, string $to, array|string $subject, array|string $mailBody, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        try {
            $username   = Arr::get($creds, "username");
            $password   = Arr::get($creds, "password");
            $host       = Arr::get($creds, "host");
            $port       = Arr::get($creds, "port");
            $encryption = Arr::get($creds, "encryption");
            $pattern    = '/[\?#\[\]@!$&\'()\*\+,;=]/';

            $encodedUsername = preg_match($pattern, $username) ? urlencode($username) : $username;
            $encodedPassword = preg_match($pattern, $password) ? urlencode($password) : $password;

            $dsn = sprintf('smtp://%s:%s@%s:%d?encryption=%s', $encodedUsername, $encodedPassword, $host, $port, $encryption);
            if ($encryption != 'ssl') {
                $dsn .= '&verify_peer=false';
            }

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $log = $dispatchLog instanceof Collection ? $dispatchLog->first() : $dispatchLog;
            $fromName = $log 
                ? (Arr::get($log->meta_data, "email_from_name", $gateway->name) 
                    ? Arr::get($log->meta_data, "email_from_name", $gateway->name) 
                    : $gateway->name)
                : $gateway->name;
            $replyTo = $log 
                ? (Arr::get($log->meta_data, "reply_to_address", $gateway->address)
                    ? Arr::get($log->meta_data, "reply_to_address", $gateway->address) 
                    : $gateway->address)
                : $gateway->address;

            $email = (new Email())
                ->from(new Address($gateway->address, $fromName))
                ->to($to)
                ->replyTo($replyTo)
                ->subject($subject)
                ->html($mailBody);

            if ($log && $log->campaign_id !== null && Arr::has($log->meta_data, "unsubscribe_link")) {
                $unsubscribeLink = Arr::get($log->meta_data, "unsubscribe_link");
                $email->getHeaders()->addTextHeader('List-Unsubscribe', '<mailto:' . $gateway->address . '?subject=unsubscribe>, <' . $unsubscribeLink . '>');
                $email->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'One-Click');
            }

            $mailer->send($email);
            return true;
        } catch (TransportException $e) {
            throw new Exception(translate("SMTP dispatch failed: ") . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception(translate("Unexpected error in SMTP dispatch: ") . $e->getMessage());
        }
    }

    /**
     * sendViaSendgrid
     *
     * @param Gateway $gateway
     * @param array $creds
     * @param array|string $to
     * @param array|string $subject
     * @param array|string $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    private function sendViaSendgrid(Gateway $gateway, array $creds, array|string $to, array|string $subject, array|string $mailBody, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        $sendgrid = new SendGrid(Arr::get($creds, "secret_key"));
        $email = new Mail();

        $fromName = $dispatchLog 
            ? ($dispatchLog instanceof Collection 
                ? (Arr::get($dispatchLog->first()->meta_data, "email_from_name") ?: $gateway->name)
                : (Arr::get($dispatchLog->meta_data, "email_from_name") ?: $gateway->name))
            : $gateway->name;

        $email->setFrom($gateway->address, $fromName);
        $email->addTo($to);

        $email->setSubject($subject);
        $email->addContent("text/html", $mailBody);

        $result = $sendgrid->send($email);

        if ($result->statusCode() < 200 || $result->statusCode() >= 300) {
            $errorMessage = json_decode($result->body())->errors[0]->message ?? "SendGrid failed with status: " . $result->statusCode();
            throw new Exception($errorMessage);
        }

        return true;
    }

    /**
     * sendViaAws
     *
     * @param Gateway $gateway
     * @param array $creds
     * @param array|string $to
     * @param array|string $subject
     * @param array|string $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    private function sendViaAws(Gateway $gateway, array $creds, array|string $to, array|string $subject, array|string $mailBody, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        $sesClient = new SesClient([
            'profile' => Arr::get($creds, "profile"),
            'version' => Arr::get($creds, "version"),
            'region' => Arr::get($creds, "region")
        ]);

        $charSet = 'UTF-8';
        $replyTo = $dispatchLog 
            ? ($dispatchLog instanceof Collection 
                ? (Arr::get($dispatchLog->first()->meta_data, "reply_to_address") ?: $gateway->address)
                : (Arr::get($dispatchLog->meta_data, "reply_to_address") ?: $gateway->address))
            : $gateway->address;

        $result = $sesClient->sendEmail([
            'Destination' => [
                'ToAddresses' => is_array($to) ? $to : [$to],
            ],
            'ReplyToAddresses' => [$replyTo],
            'Source' => Arr::get($creds, "sender_email"),
            'Message' => [
                'Body' => [
                    'Html' => [
                        'Charset' => $charSet,
                        'Data' => is_array($mailBody) ? $mailBody[0] : $mailBody,
                    ],
                ],
                'Subject' => [
                    'Charset' => $charSet,
                    'Data' => is_array($subject) ? $subject[0] : $subject,
                ],
            ],
            'ConfigurationSetName' => 'ConfigSet',
        ]);

        return true; 
    }

    /**
     * sendViaMailjet
     *
     * @param Gateway $gateway
     * @param array $creds
     * @param array|string $to
     * @param array|string $subject
     * @param array|string $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    private function sendViaMailjet(Gateway $gateway, array $creds, array|string $to, array|string $subject, array|string $mailBody, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        $emailFrom = $dispatchLog 
            ? ($dispatchLog instanceof Collection 
                ? (Arr::get($dispatchLog->first()->meta_data, "email_from_name") ?: $gateway->name)
                : (Arr::get($dispatchLog->meta_data, "email_from_name") ?: $gateway->name))
            : $gateway->name;
        $replyTo = $dispatchLog 
            ? ($dispatchLog instanceof Collection 
                ? (Arr::get($dispatchLog->first()->meta_data, "reply_to_address") ?: $gateway->address)
                : (Arr::get($dispatchLog->meta_data, "reply_to_address") ?: $gateway->address))
            : $gateway->address;

        $messages = is_array($to) 
            ? array_map(function ($recipient) use ($subject, $mailBody, $replyTo, $emailFrom) {
                return [
                    'From' => ['Email' => $replyTo, 'Name' => $emailFrom],
                    'To' => [['Email' => $recipient, 'Name' => explode('@', $recipient)[0]]],
                    'Subject' => is_array($subject) ? $subject[0] : $subject,
                    'TextPart' => ' ',
                    'HTMLPart' => is_array($mailBody) ? $mailBody[0] : $mailBody
                ];
            }, $to)
            : [[
                'From' => ['Email' => $replyTo, 'Name' => $emailFrom],
                'To' => [['Email' => $to, 'Name' => explode('@', $to)[0]]],
                'Subject' => $subject,
                'TextPart' => ' ',
                'HTMLPart' => $mailBody
            ]];

        $body = ['Messages' => $messages];
        $client = new Client(['base_uri' => 'https://api.mailjet.com/v3.1/']);
        $response = $client->request('POST', 'send', [
            'json' => $body,
            'auth' => [Arr::get($creds, "api_key"), Arr::get($creds, "secret_key")]
        ]);

        $responseBody = json_decode($response->getBody());
        if ($response->getStatusCode() != 200 || $responseBody->Messages[0]->Status != 'success') {
            $errorMessage = $responseBody->Messages[0]->Status ?? "Mailjet failed with status: " . $response->getStatusCode();
            throw new Exception($errorMessage);
        }

        return true;
    }

    /**
     * sendViaMailgun
     *
     * @param Gateway $gateway
     * @param array $creds
     * @param array|string $to
     * @param array|string $subject
     * @param array|string $mailBody
     * @param array|DispatchLog|Collection|null $dispatchLog
     * 
     * @return bool
     */
    private function sendViaMailgun(Gateway $gateway, array $creds, array|string $to, array|string $subject, array|string $mailBody, array|DispatchLog|Collection|null $dispatchLog = null): bool
    {
        $mailGun = Mailgun::create(Arr::get($creds, "secret_key"));
        $domain = Arr::get($creds, "verified_domain");

        $result = $mailGun->messages()->send($domain, [
            'from' => $gateway->address,
            'to' => is_array($to) ? implode(',', $to) : $to,
            'subject' => is_array($subject) ? $subject[0] : $subject,
            'html' => is_array($mailBody) ? $mailBody[0] : $mailBody
        ]);

        return true;
    }
    
}