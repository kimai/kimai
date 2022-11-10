<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectDetails;

use App\Form\Type\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectFormTypeQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectDetailsForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $projectOptions = [
            'ignore_date' => true,
            'required' => false,
            'width' => false,
            'join_customer' => true,
        ];

        $builder->add('project', ProjectType::class, $projectOptions);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $projectOptions) {
                $data = $event->getData();
                if (isset($data['project']) && !empty($data['project'])) {
                    $projectId = $data['project'];
                    $projects = [];
                    if (\is_int($projectId) || \is_string($projectId)) {
                        $projects = [$projectId];
                    }

                    $event->getForm()->add('project', ProjectType::class, array_merge($projectOptions, [
                        'projects' => $projects
                    ]));
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProjectDetailsQuery::class,
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
