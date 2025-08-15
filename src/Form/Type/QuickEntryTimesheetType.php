<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Timesheet;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class QuickEntryTimesheetType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $durationOptions = [
            'label' => false,
            'required' => false,
            'attr' => [
                'placeholder' => '0:00',
            ],
            'icon' => null,
        ];

        $duration = $options['duration_minutes'];
        if ($duration !== null && (int) $duration > 0) {
            $durationOptions = array_merge($durationOptions, [
                'preset_minutes' => $duration
            ]);
        }

        $duration = $options['duration_hours'];
        if ($duration !== null && (int) $duration > 0) {
            $durationOptions = array_merge($durationOptions, [
                'preset_hours' => $duration,
            ]);
        }

        $builder->add('duration', DurationType::class, $durationOptions);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($durationOptions): void {
                /** @var Timesheet|null $data */
                $data = $event->getData();
                if (null === $data || $data->isRunning()) {
                    $event->getForm()->get('duration')->setData(null);
                }

                if ($data instanceof Timesheet && !$this->security->isGranted('edit', $data)) {
                    // do not call $event->getForm()->remove() this would change the field order
                    $event->getForm()->add('duration', DurationType::class, array_merge(['disabled' => true], $durationOptions));

                    $mainForm = $event->getForm()->getParent()?->getParent();
                    if ($mainForm === null) {
                        return;
                    }

                    $isNew = $data->getId() === null;

                    foreach($mainForm->all() as $key => $child) {
                        if ($key === 'timesheets') {
                            continue;
                        }
                        if ($child->isDisabled() || $isNew) {
                            continue;
                        }
                        $type = \get_class($child->getConfig()->getType()->getInnerType());
                        // do not call $mainForm->remove() this would change the field order
                        $mainForm->add($key, $type, array_merge($child->getConfig()->getOptions(), ['disabled' => true]));
                    }
                }
            }
        );

        // make sure that duration is mapped back to end field
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event): void {
                /** @var Timesheet $data */
                $data = $event->getData();
                $duration = $data->getDuration(false);
                try {
                    if (null !== $duration) {
                        $end = clone $data->getBegin();
                        $end->modify('+ ' . abs($duration) . ' seconds');
                        $data->setEnd($end);
                    } else {
                        $data->setDuration(null);
                    }
                } catch (\Exception $e) {
                    $event->getForm()->addError(new FormError($e->getMessage()));
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Timesheet::class,
            'timezone' => date_default_timezone_get(),
            'duration_minutes' => null,
            'duration_hours' => 10,
        ]);
    }
}
