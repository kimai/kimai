<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\EventSubscriber;

use App\Entity\TimesheetMeta;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Event\TimesheetMetaDisplayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class NotionTimesheetMetaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ?string $notionWorkspace = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDefinitionEvent::class => ['onTimesheetMetaDefinition', 200],
            TimesheetMetaDisplayEvent::class => ['onTimesheetMetaDisplay', 200],
        ];
    }

    public function onTimesheetMetaDefinition(TimesheetMetaDefinitionEvent $event): void
    {
        // Skip if Notion workspace is not configured
        if (empty($this->notionWorkspace)) {
            return;
        }

        // Add a URL field for the Notion link
        $definition = (new TimesheetMeta())
            ->setName('notion_link')
            ->setLabel('Notion Link')
            ->setType(UrlType::class)
            ->setOptions([
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'class' => 'form-control',
                ],
                'help' => 'Link to the Notion time entry',
            ])
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }

    public function onTimesheetMetaDisplay(TimesheetMetaDisplayEvent $event): void
    {
        // Skip if Notion workspace is not configured
        if (empty($this->notionWorkspace)) {
            return;
        }

        foreach ($event->getFields() as $field) {
            if ($field->getName() === 'notion_link') {
                $field->setLabel('Notion');
            }
        }
    }

    /**
     * Build the Notion URL from the page ID
     */
    public function buildNotionUrl(string $pageId): string
    {
        // Remove hyphens from the page ID for the URL
        $cleanPageId = str_replace('-', '', $pageId);
        
        return sprintf('https://www.notion.so/%s/%s', $this->notionWorkspace, $cleanPageId);
    }
}

