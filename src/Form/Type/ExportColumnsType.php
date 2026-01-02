<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Entity\MetaTableTypeInterface;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExportColumnsType extends AbstractType
{
    /**
     * @var array<int, string>
     */
    private array $ordered = [];

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly TranslatorInterface $translator,
        private readonly SystemConfiguration $configuration,
    )
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $columns = [
            'timesheet' => [
                'id' => 'id',
                'date' => 'date',
                'begin' => 'begin',
                'end' => 'end',
                $this->translator->trans('duration') . ' (1:30)' => 'duration',
                $this->translator->trans('duration') . ' (1:30:00)' => 'duration_seconds',
                $this->translator->trans('duration') . ' (1.5)' => 'duration_decimal',
                'currency' => 'currency',
                'rate' => 'rate',
                'internalRate' => 'internal_rate',
                'hourlyRate' => 'hourly_rate',
                'fixedRate' => 'fixed_rate',
                'description' => 'description',
                'exported' => 'exported',
                'billable' => 'billable',
                'tags' => 'tags',
                'type' => 'type',
                'category' => 'category',
            ],
            'user' => [
                'alias' => 'user.alias',
                'username' => 'user.name',
                'account_number' => 'user.account_number',
            ],
            'customer' => [
                'customer' => 'customer.name',
                'number' => 'customer.number',
                'vat_id' => 'customer.vat_id',
            ],
            'project' => [
                'project' => 'project.name',
                'project_number' => 'project.number',
                'orderNumber' => 'project.order_number',
            ],
            'activity' => [
                'activity' => 'activity.name',
                'activity_number' => 'activity.number',
            ],
        ];

        if ($this->configuration->isBreakTimeEnabled()) {
            $tmp = [
                $this->translator->trans('break') . ' (0:30)' => 'break',
                $this->translator->trans('break') . ' (0:30:00)' => 'break_seconds',
                $this->translator->trans('break') . ' (0.5)' => 'break_decimal',
            ];
            foreach ($tmp as $k => $v) {
                $columns['timesheet'][$k] = $v;
            }
        }

        foreach ($this->findMetaColumns(new TimesheetMetaDisplayEvent(new TimesheetQuery(), TimesheetMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns['timesheet'][$metaField->getLabel()] = 'timesheet.meta.' . $metaField->getName();
            }
        }

        foreach ($this->findMetaColumns(new CustomerMetaDisplayEvent(new CustomerQuery(), CustomerMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns['customer'][$metaField->getLabel()] = 'customer.meta.' . $metaField->getName();
            }
        }

        foreach ($this->findMetaColumns(new ProjectMetaDisplayEvent(new ProjectQuery(), ProjectMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns['project'][$metaField->getLabel()] = 'project.meta.' . $metaField->getName();
            }
        }

        foreach ($this->findMetaColumns(new ActivityMetaDisplayEvent(new ActivityQuery(), ActivityMetaDisplayEvent::EXPORT)) as $metaField) {
            if ($metaField->getName() !== null) {
                $columns['activity'][$metaField->getLabel()] = 'activity.meta.' . $metaField->getName();
            }
        }

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->dispatcher->dispatch($event);
        foreach ($event->getPreferences() as $metaField) {
            if ($metaField->getName() !== null) {
                $columns['user'][$metaField->getLabel()] = 'user.meta.' . $metaField->getName();
            }
        }

        $resolver->setDefaults([
            'choices' => $columns,
            'label' => 'modal.columns.label',
            'multiple' => true,
            // does not work in the frontend
            //'order' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $data = $event->getData();
                if (\is_array($data)) {
                    $this->ordered = $data; // @phpstan-ignore assign.propertyType
                }
            }
        );
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event): void {
                $event->setData($this->ordered);
            }
        );
    }

    /**
     * @return array<MetaTableTypeInterface>
     */
    private function findMetaColumns(MetaDisplayEventInterface $event): array
    {
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
