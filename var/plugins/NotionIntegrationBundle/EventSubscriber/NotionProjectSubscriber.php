<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\EventSubscriber;

use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Event\ProjectCreatePreEvent;
use App\Event\ProjectMetaDefinitionEvent;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\ProjectUpdatePreEvent;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\NotionIntegrationBundle\Service\NotionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class NotionProjectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotionService $notionService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProjectMetaDefinitionEvent::class => ['onProjectMetaDefinition', 200],
            ProjectCreatePreEvent::class => ['onProjectSaving', 100],
            ProjectUpdatePreEvent::class => ['onProjectSaving', 100],
            ProjectMetaDisplayEvent::class => ['onProjectMetaDisplay', 200],
        ];
    }

    public function onProjectMetaDefinition(ProjectMetaDefinitionEvent $event): void
    {
        $projects = $this->notionService->getProjects();

        // Only add the field if we have Notion projects
        if (empty($projects)) {
            return;
        }

        $this->logger->info('onProjectMetaDefinition', [
            'adding_notion_dropdown' => true,
            'project_count' => count($projects)
        ]);

        // Add the dropdown for selecting Notion project
        $definition = (new ProjectMeta())
            ->setName('notion_project_id')
            ->setLabel('Notion Project ID')
            ->setType(ChoiceType::class)
            ->setOptions([
                'choices' => $projects,
                'placeholder' => 'Select a Notion project (optional)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Link this project to a Notion project',
            ])
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
        
        // Also add the notion_project_name field as a read-only display field
        // This is disabled so it won't be saved by the form (the onProjectSaving event handles that)
        $nameDefinition = (new ProjectMeta())
            ->setName('notion_project_name')
            ->setLabel('Notion Project Name')
            ->setType(TextType::class)
            ->setOptions([
                'required' => false,
                'disabled' => true,
                'attr' => [
                    'readonly' => true,
                    'class' => 'form-control',
                ],
                'help' => 'Auto-populated from the selected Notion project',
            ])
            ->setIsVisible(true);
        
        $event->getEntity()->setMetaField($nameDefinition);
        
        // Store projects data for later use
        $this->projectsCache = $projects;
    }
    
    public function onProjectSaving($event): void
    {
        $project = $event->getProject();
        $notionId = $project->getMetaField('notion_project_id')?->getValue();
        
        // If a Notion project is selected, auto-populate the name field
        if ($notionId) {
            $projects = $this->notionService->getProjects();
            
            if (!empty($projects)) {
                // projects is array of name => id, so flip it to find name by id
                $projectsReverse = array_flip($projects);
                $notionName = $projectsReverse[$notionId] ?? null;
                
                if ($notionName) {
                    // Check if the meta field already exists in the entity
                    $nameMeta = $project->getMetaField('notion_project_name');
                    
                    // If not on the entity, check if it exists in the database
                    if ($nameMeta === null && $project->getId() !== null) {
                        $nameMeta = $this->entityManager
                            ->getRepository(ProjectMeta::class)
                            ->findOneBy([
                                'project' => $project,
                                'name' => 'notion_project_name'
                            ]);
                        
                        // If we found it in the database, attach it to the project entity
                        if ($nameMeta !== null) {
                            $project->setMetaField($nameMeta);
                        }
                    }
                    
                    // If still null, create a new one
                    if ($nameMeta === null) {
                        $nameMeta = new ProjectMeta();
                        $nameMeta->setName('notion_project_name');
                        $nameMeta->setLabel('Notion Project');
                        $nameMeta->setIsVisible(true);
                        $project->setMetaField($nameMeta);
                    }
                    
                    // Update the value (this will trigger Doctrine's change tracking)
                    $nameMeta->setValue($notionName);
                    
                    $this->logger->info('onProjectSaving', [
                        'project_id' => $project->getId(),
                        'notion_project_id' => $notionId,
                        'notion_project_name' => $notionName,
                        'will_be_saved' => true
                    ]);
                }
            }
        } else {
            // If Notion project is cleared, also clear the name
            $nameMeta = $project->getMetaField('notion_project_name');
            
            // If not on entity, check database
            if ($nameMeta === null && $project->getId() !== null) {
                $nameMeta = $this->entityManager
                    ->getRepository(ProjectMeta::class)
                    ->findOneBy([
                        'project' => $project,
                        'name' => 'notion_project_name'
                    ]);
                
                // If we found it in the database, attach it to the project entity
                if ($nameMeta !== null) {
                    $project->setMetaField($nameMeta);
                }
            }
            
            if ($nameMeta !== null) {
                $nameMeta->setValue(null);
                
                $this->logger->info('onProjectSaving', [
                    'project_id' => $project->getId(),
                    'notion_project_cleared' => true
                ]);
            }
        }
    }
    
    public function onProjectMetaDisplay(ProjectMetaDisplayEvent $event): void
    {
        // Only add the field to the PROJECT location (not EXPORT or other locations)
        if ($event->getLocation() !== ProjectMetaDisplayEvent::PROJECT) {
            return;
        }
        
        // Check if Notion integration is configured
        if (empty($this->notionService->getProjects())) {
            return;
        }
        
        // Add the notion_project_name field as a displayable column
        $field = new ProjectMeta();
        $field->setName('notion_project_name');
        $field->setLabel('Notion Project');
        $field->setIsVisible(true);
        
        $event->addField($field);
        
        $this->logger->info('onProjectMetaDisplay', [
            'adding_notion_column' => true,
            'location' => $event->getLocation()
        ]);
    }
    
    private array $projectsCache = [];
}
