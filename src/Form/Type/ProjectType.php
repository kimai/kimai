<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Utils\LocaleSettings;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a project.
 */
class ProjectType extends AbstractType
{
    public const PATTERN_NAME = '{name}';
    public const PATTERN_COMMENT = '{comment}';
    public const PATTERN_ORDERNUMBER = '{ordernumber}';
    public const PATTERN_DATERANGE = '{daterange}';
    public const PATTERN_START = '{start}';
    public const PATTERN_END = '{end}';
    public const PATTERN_SPACER = '{spacer}';
    public const SPACER = ' - ';

    private $configuration;
    private $localeSettings;
    private $dateFormat;
    private $pattern;

    public function __construct(SystemConfiguration $configuration, LocaleSettings $localeSettings)
    {
        $this->configuration = $configuration;
        $this->localeSettings = $localeSettings;
    }

    private function getPattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->configuration->find('project.choice_pattern');

            if ($this->pattern === null || stripos($this->pattern, '{') === false || stripos($this->pattern, '}') === false) {
                $this->pattern = self::PATTERN_NAME;
            }

            $this->pattern = str_replace(self::PATTERN_DATERANGE, self::PATTERN_START . '-' . self::PATTERN_END, $this->pattern);
            $this->pattern = str_replace(self::PATTERN_SPACER, self::SPACER, $this->pattern);
        }

        return $this->pattern;
    }

    public function getChoiceLabel(Project $project): string
    {
        if ($this->dateFormat === null) {
            $this->dateFormat = $this->localeSettings->getDateFormat();
        }

        $start = '?';
        if ($project->getStart() !== null) {
            $start = $project->getStart()->format($this->dateFormat);
        }

        $end = '?';
        if ($project->getEnd() !== null) {
            $end = $project->getEnd()->format($this->dateFormat);
        }

        $name = $this->getPattern();
        $name = str_replace(self::PATTERN_NAME, $project->getName(), $name);
        $name = str_replace(self::PATTERN_COMMENT, $project->getComment() ?? '', $name);
        $name = str_replace(self::PATTERN_ORDERNUMBER, $project->getOrderNumber(), $name);
        $name = str_replace(self::PATTERN_START, $start, $name);
        $name = str_replace(self::PATTERN_END, $end, $name);

        $name = ltrim($name, self::SPACER);
        $name = rtrim($name, self::SPACER);
        $name = str_replace('- ?-?', '', $name);

        if ($name === '' || $name === self::SPACER) {
            $name = $project->getName();
        }

        return substr($name, 0, 110);
    }

    /**
     * @param Project $project
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function getChoiceAttributes(Project $project, $key, $value): array
    {
        if (null !== ($customer = $project->getCustomer())) {
            return ['data-customer' => $customer->getId(), 'data-currency' => $customer->getCurrency()];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // documentation is for NelmioApiDocBundle
            'documentation' => [
                'type' => 'integer',
                'description' => 'Project ID',
            ],
            'label' => 'label.project',
            'class' => Project::class,
            'choice_label' => [$this, 'getChoiceLabel'],
            'choice_attr' => [$this, 'getChoiceAttributes'],
            'group_by' => function (Project $project, $key, $index) {
                return $project->getCustomer()->getName();
            },
            'query_builder_for_user' => true,
            'activity_enabled' => false,
            'activity_select' => 'activity',
            'activity_visibility' => ActivityQuery::SHOW_VISIBLE,
            'ignore_date' => false,
            'join_customer' => false,
            // @var Project|null
            'ignore_project' => null,
            // @var Customer|Customer[]|int|int[]|null
            'customers' => null,
            // @var Project|Project[]|int|int[]|null
            'projects' => null,
            // @var DateTime|null
            'project_date_start' => null,
            // @var DateTime|null
            'project_date_end' => null,
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (ProjectRepository $repo) use ($options) {
                $query = new ProjectFormTypeQuery($options['projects'], $options['customers']);
                if (true === $options['query_builder_for_user']) {
                    $query->setUser($options['user']);
                }

                if (true === $options['ignore_date']) {
                    $query->setIgnoreDate(true);
                } else {
                    if ($options['project_date_start'] !== null) {
                        $query->setProjectStart($options['project_date_start']);
                    }
                    if ($options['project_date_end'] !== null) {
                        $query->setProjectEnd($options['project_date_end']);
                    }
                }

                if (true === $options['join_customer']) {
                    $query->setWithCustomer(true);
                }

                if (null !== $options['ignore_project']) {
                    $query->setProjectToIgnore($options['ignore_project']);
                }

                return $repo->getQueryBuilderForFormType($query);
            };
        });

        $resolver->setDefault('api_data', function (Options $options) {
            if (false !== $options['activity_enabled']) {
                $name = \is_string($options['activity_enabled']) ? $options['activity_enabled'] : 'project';

                return [
                    'select' => $options['activity_select'],
                    'route' => 'get_activities',
                    'route_params' => [$name => '%' . $name . '%', 'visible' => $options['activity_visibility']],
                    'empty_route_params' => ['globals' => 'true', 'visible' => $options['activity_visibility']],
                ];
            }

            return [];
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-option-pattern' => $this->getPattern(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
