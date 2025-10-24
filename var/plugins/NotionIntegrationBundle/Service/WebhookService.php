<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\Service;

use App\Entity\Timesheet;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebhookService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $webhookUrl,
        private readonly string $webhookSecret = ''
    ) {
    }

    public function sendTimesheetWebhook(Timesheet $timesheet, string $event): void
    {
        // Skip if no webhook URL is configured
        if (empty($this->webhookUrl)) {
            $this->logger->debug('Webhook URL not configured, skipping webhook', ['event' => $event]);
            return;
        }

        try {
            $payload = $this->buildTimesheetPayload($timesheet, $event);
            $headers = $this->buildHeaders($payload);

            $this->logger->info('Dispatching webhook (async)', [
                'event' => $event,
                'url' => $this->webhookUrl,
                'timesheet_id' => $timesheet->getId()
            ]);

            // Send webhook asynchronously - don't wait for response
            // By not calling getContent() or getStatusCode(), the request is non-blocking
            $this->httpClient->request('POST', $this->webhookUrl, [
                'headers' => $headers,
                'json' => $payload,
                'timeout' => 5, // Connection timeout - 5 seconds should be enough
                'max_duration' => 10, // Max 10 seconds for the full request
            ]);

            // The request will complete in the background
            // Don't wait for or check the response
        } catch (\Exception $e) {
            // Log error but don't block the request
            $this->logger->error('Failed to dispatch webhook', [
                'event' => $event,
                'error' => $e->getMessage(),
                'timesheet_id' => $timesheet->getId() ?? null
            ]);
        }
    }

    public function buildTimesheetPayload(Timesheet $timesheet, string $event): array
    {
        $payload = [
            'event' => $event,
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            'timesheet' => [
                'id' => $timesheet->getId(),
                'begin' => $timesheet->getBegin()?->format(\DateTime::ATOM),
                'end' => $timesheet->getEnd()?->format(\DateTime::ATOM),
                'timezone' => $timesheet->getTimezone(),
                'duration' => $timesheet->getDuration(),
                'break' => $timesheet->getBreak(),
                'rate' => $timesheet->getRate(),
                'hourly_rate' => $timesheet->getHourlyRate(),
                'fixed_rate' => $timesheet->getFixedRate(),
                'internal_rate' => $timesheet->getInternalRate(),
                'description' => $timesheet->getDescription(),
                'exported' => $timesheet->isExported(),
                'billable' => $timesheet->isBillable(),
                'billable_mode' => $timesheet->getBillableMode(),
                'category' => $timesheet->getCategory(),
                'modified_at' => $timesheet->getModifiedAt()?->format(\DateTime::ATOM),
                'user' => [
                    'id' => $timesheet->getUser()->getId(),
                    'username' => $timesheet->getUser()->getUserIdentifier(),
                    'alias' => $timesheet->getUser()->getAlias(),
                    'title' => $timesheet->getUser()->getTitle(),
                    'email' => $timesheet->getUser()->getEmail(),
                ],
            ],
        ];

        // Add project information if available
        if ($timesheet->getProject() !== null) {
            $payload['timesheet']['project'] = [
                'id' => $timesheet->getProject()->getId(),
                'name' => $timesheet->getProject()->getName(),
                'order_number' => $timesheet->getProject()->getOrderNumber(),
                'comment' => $timesheet->getProject()->getComment(),
                'visible' => $timesheet->getProject()->isVisible(),
                'billable' => $timesheet->getProject()->isBillable(),
            ];

            // Add customer information if available
            if ($timesheet->getProject()->getCustomer() !== null) {
                $payload['timesheet']['customer'] = [
                    'id' => $timesheet->getProject()->getCustomer()->getId(),
                    'name' => $timesheet->getProject()->getCustomer()->getName(),
                    'number' => $timesheet->getProject()->getCustomer()->getNumber(),
                    'comment' => $timesheet->getProject()->getCustomer()->getComment(),
                    'visible' => $timesheet->getProject()->getCustomer()->isVisible(),
                ];
            }
        }

        // Add activity information if available
        if ($timesheet->getActivity() !== null) {
            $payload['timesheet']['activity'] = [
                'id' => $timesheet->getActivity()->getId(),
                'name' => $timesheet->getActivity()->getName(),
                'comment' => $timesheet->getActivity()->getComment(),
                'visible' => $timesheet->getActivity()->isVisible(),
            ];
        }

        // Add tags if available
        if (!$timesheet->getTags()->isEmpty()) {
            $payload['timesheet']['tags'] = $timesheet->getTagsAsArray();
        }

        // Add meta fields if available
        $metaFields = [];
        foreach ($timesheet->getMetaFields() as $meta) {
            $metaFields[$meta->getName()] = $meta->getValue();
        }
        if (!empty($metaFields)) {
            $payload['timesheet']['meta_fields'] = $metaFields;
        }

        return $payload;
    }

    private function buildHeaders(array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Kimai-Webhook/1.0',
        ];

        // Add signature header if secret is configured
        if (!empty($this->webhookSecret)) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
            $headers['X-Kimai-Signature'] = 'sha256=' . $signature;
        }

        return $headers;
    }
}


