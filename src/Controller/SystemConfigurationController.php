<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\ConfigurationService;
use App\Configuration\SystemConfiguration;
use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration as SystemConfigurationModel;
use App\Form\SystemConfigurationForm;
use App\Form\Type\ActivityTypePatternType;
use App\Form\Type\ArrayToCommaStringType;
use App\Form\Type\CalendarTitlePatternType;
use App\Form\Type\CustomerTypePatternType;
use App\Form\Type\DatePickerType;
use App\Form\Type\DateTimeTextType;
use App\Form\Type\DayTimeType;
use App\Form\Type\MinuteIncrementType;
use App\Form\Type\ProjectTypePatternType;
use App\Form\Type\RoundingModeType;
use App\Form\Type\SkinType;
use App\Form\Type\TimezoneType;
use App\Form\Type\TrackingModeType;
use App\Form\Type\UserLanguageType;
use App\Form\Type\WeekDaysType;
use App\Form\Type\YesNoType;
use App\Timesheet\LockdownService;
use App\Utils\PageSetup;
use App\Validator\Constraints\ColorChoices;
use App\Validator\Constraints\DateTimeFormat;
use App\Validator\Constraints\TimeFormat;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Controller used for executing system relevant tasks.
 */
#[Route(path: '/admin/system-config')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted('system_configuration')]
final class SystemConfigurationController extends AbstractController
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private ConfigurationService $repository, private SystemConfiguration $systemConfiguration, private LockdownService $lockdownService)
    {
    }

    #[Route(path: '/', name: 'system_configuration', methods: ['GET'])]
    public function indexAction(): Response
    {
        $configSettings = $this->getInitializedConfigurations();

        $configurations = [];
        foreach ($configSettings as $configModel) {
            $configurations[] = [
                'model' => $configModel,
                'form' => $this->createConfigurationsForm($configModel)->createView(),
            ];
        }

        $page = new PageSetup('menu.system_configuration');
        $page->setHelp('configurations.html');

        return $this->render('system-configuration/index.html.twig', [
            'page_setup' => $page,
            'sections' => $configurations,
        ]);
    }

    #[Route(path: '/edit/{section}', name: 'system_configuration_section', methods: ['GET'])]
    public function sectionAction(string $section): Response
    {
        $configSettings = $this->getInitializedConfigurations();

        $configurations = [];
        foreach ($configSettings as $configModel) {
            if ($configModel->getSection() !== $section) {
                continue;
            }

            $configurations[] = [
                'model' => $configModel,
                'form' => $this->createConfigurationsForm($configModel, true)->createView(),
            ];
        }

        $page = new PageSetup('menu.system_configuration');
        $page->setHelp('configurations.html');

        return $this->render('system-configuration/section.html.twig', [
            'page_setup' => $page,
            'sections' => $configurations,
        ]);
    }

    /**
     * @internal do not link directly to this route
     * @param Request $request
     * @param string $section
     * @return RedirectResponse|Response
     */
    #[Route(path: '/update/{section}/{single}', defaults: ['single' => 0], name: 'system_configuration_update', methods: ['POST'])]
    public function configUpdate(Request $request, string $section, string $single): Response
    {
        $single = (bool) $single;
        $configModel = null;
        $configSettings = $this->getInitializedConfigurations();

        foreach ($configSettings as $model) {
            if ($model->getSection() === $section) {
                $configModel = $model;
                break;
            }
        }

        if (null === $configModel) {
            throw $this->createNotFoundException('Could not find config model: ' . $section);
        }

        $form = $this->createConfigurationsForm($configModel, $single);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var SystemConfigurationModel $saveModel */
                $saveModel = $form->getData();
                $this->repository->saveSystemConfiguration($saveModel);
                $this->flashSuccess('action.update.success');
            } catch (\Exception $ex) {
                $this->handleFormUpdateException($ex, $form);
            }

            if ($single) {
                return $this->redirectToRoute('system_configuration_section', ['section' => $section]);
            }

            return $this->redirectToRoute('system_configuration');
        }

        $configSettings = $this->getInitializedConfigurations();

        $configurations = [];
        foreach ($configSettings as $model) {
            if ($single && $section !== $model->getSection()) {
                continue;
            }

            if ($section !== $model->getSection()) {
                $form2 = $this->createConfigurationsForm($model, $single);
            } else {
                $form2 = $form;
            }
            $configurations[] = [
                'model' => $model,
                'form' => $form2->createView(),
            ];
        }

        return $this->render('system-configuration/index.html.twig', [
            'sections' => $configurations,
        ]);
    }

    private function createConfigurationsForm(SystemConfigurationModel $configuration, bool $isSingleSection = false): FormInterface
    {
        $options = [
            'action' => $this->generateUrl('system_configuration_update', ['section' => $configuration->getSection(), 'single' => $isSingleSection ? '1' : '0']),
            'method' => 'POST',
        ];

        return $this->container
            ->get('form.factory')
            ->createNamedBuilder('system_configuration_form_' . $configuration->getSection(), SystemConfigurationForm::class, $configuration, $options)
            ->getForm();
    }

    /**
     * @return SystemConfigurationModel[]
     */
    private function getInitializedConfigurations(): array
    {
        $types = $this->getConfigurationTypes();

        $event = new SystemConfigurationEvent($types);
        $this->eventDispatcher->dispatch($event);

        foreach ($event->getConfigurations() as $configs) {
            foreach ($configs->getConfiguration() as $config) {
                if (!$this->systemConfiguration->has($config->getName())) {
                    continue;
                }

                $configValue = $this->systemConfiguration->find($config->getName());
                if (null !== $configValue) {
                    $config->setValue($configValue);
                }
            }
        }

        return $event->getConfigurations();
    }

    /**
     * @return SystemConfigurationModel[]
     */
    private function getConfigurationTypes(): array
    {
        $user = $this->getUser();
        $lockdownStartHelp = null;
        $lockdownEndHelp = null;
        $lockdownGraceHelp = null;
        $dateFormat = 'D, d M Y H:i:s';

        if ($this->lockdownService->isLockdownActive()) {
            try {
                $start = $this->lockdownService->getLockdownStart($user);
                if ($start !== null) {
                    $lockdownStartHelp = $start->format($dateFormat);
                }

                $end = $this->lockdownService->getLockdownEnd($user);
                if ($end !== null) {
                    $lockdownEndHelp = $end->format($dateFormat);
                }

                $grace = $this->lockdownService->getLockdownGrace($user);
                if ($grace !== null) {
                    $lockdownGraceHelp = $grace->format($dateFormat);
                }
            } catch (\Exception $ex) {
                $lockdownStartHelp = 'invalid';
            }
        }

        $authentication = (new SystemConfigurationModel('authentication'))
            ->setConfiguration([
                (new Configuration('user.login'))
                    ->setLabel('user_auth_login')
                    ->setTranslationDomain('system-configuration')
                    ->setType(YesNoType::class),
                (new Configuration('user.registration'))
                    ->setLabel('user_auth_registration')
                    ->setTranslationDomain('system-configuration')
                    ->setType(YesNoType::class),
                (new Configuration('user.password_reset'))
                    ->setTranslationDomain('system-configuration')
                    ->setLabel('user_auth_password_reset')
                    ->setType(YesNoType::class),
                (new Configuration('user.password_reset_retry_ttl'))
                    ->setTranslationDomain('system-configuration')
                    ->setLabel('user_auth_password_reset_retry_ttl')
                    ->setConstraints([new NotNull(), new GreaterThanOrEqual(['value' => 60])])
                    ->setType(IntegerType::class),
                (new Configuration('user.password_reset_token_ttl'))
                    ->setTranslationDomain('system-configuration')
                    ->setLabel('user_auth_password_reset_token_ttl')
                    ->setConstraints([new NotNull(), new GreaterThanOrEqual(['value' => 60])])
                    ->setType(IntegerType::class),
            ]);

        $allowRegistration = $this->systemConfiguration->find('features.user_registration');
        if ($allowRegistration === false) {
            $authentication->getConfigurationByName('user.registration')?->setEnabled(false);
        }

        if (!$this->systemConfiguration->isSamlActive()) {
            $authentication->getConfigurationByName('user.login')?->setEnabled(false);
        }

        if (!$this->systemConfiguration->isPasswordResetActive()) {
            $authentication->getConfigurationByName('user.password_reset_retry_ttl')?->setEnabled(false);
            $authentication->getConfigurationByName('user.password_reset_token_ttl')?->setEnabled(false);
        }

        return [
            (new SystemConfigurationModel('timesheet'))
                ->setConfiguration([
                    (new Configuration('timesheet.mode'))
                        ->setType(TrackingModeType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.default_begin'))
                        ->setType(DateTimeTextType::class)
                        ->setConstraints([new DateTimeFormat(), new NotNull()])
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.allow_future_times'))
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.allow_zero_duration'))
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.allow_overlapping_records'))
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.allow_overbooking_budget'))
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.active_entries.hard_limit'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 1])
                        ]),
                    (new Configuration('timesheet.time_increment'))
                        ->setType(MinuteIncrementType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new Range(['min' => 0, 'max' => 60])
                        ]),
                    (new Configuration('timesheet.duration_increment'))
                        ->setType(MinuteIncrementType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    /*
                    (new Configuration('timesheet.rules.break_warning_duration'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    */
                    (new Configuration('timesheet.rules.long_running_duration'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                ]),
            (new SystemConfigurationModel('quick_entry'))
                ->setTranslation('quick_entry.title')
                ->setTranslationDomain('messages')
                ->setConfiguration([
                    (new Configuration('quick_entry.recent_activities'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setConstraints([
                            new Range(['min' => 0, 'max' => 20]),
                        ]),
                    (new Configuration('quick_entry.recent_activity_weeks'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setConstraints([
                            new Range(['min' => 0, 'max' => 20]),
                        ]),
                    (new Configuration('quick_entry.minimum_rows'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new Range(['min' => 1, 'max' => 5]),
                        ]),
                ]),
            (new SystemConfigurationModel('lockdown_period'))
                ->setConfiguration([
                    (new Configuration('timesheet.rules.lockdown_period_start'))
                        ->setOptions(['help' => $lockdownStartHelp])
                        ->setType(TextType::class)
                        ->setRequired(false)
                        ->setConstraints([new DateTimeFormat(['separator' => ','])])
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.lockdown_period_end'))
                        ->setOptions(['help' => $lockdownEndHelp])
                        ->setType(TextType::class)
                        ->setRequired(false)
                        ->setConstraints([new DateTimeFormat(['separator' => ','])])
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.lockdown_period_timezone'))
                        ->setType(TimezoneType::class)
                        ->setRequired(false)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rules.lockdown_grace_period'))
                        ->setOptions(['help' => $lockdownGraceHelp])
                        ->setType(TextType::class)
                        ->setRequired(false)
                        ->setConstraints([new DateTimeFormat()])
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel('rounding'))
                ->setConfiguration([
                    (new Configuration('timesheet.rounding.default.mode'))
                        ->setType(RoundingModeType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('timesheet.rounding.default.begin'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    (new Configuration('timesheet.rounding.default.end'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    (new Configuration('timesheet.rounding.default.duration'))
                        ->setType(IntegerType::class)
                        ->setTranslationDomain('system-configuration')
                        ->setConstraints([
                            new GreaterThanOrEqual(['value' => 0])
                        ]),
                    (new Configuration('timesheet.rounding.default.days'))
                        ->setType(WeekDaysType::class)
                        ->setRequired(false)
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel('invoice'))
                ->setTranslation('invoices')
                ->setTranslationDomain('messages')
                ->setConfiguration([
                    // TODO that should be a custom type with validation
                    (new Configuration('invoice.number_format'))
                        ->setLabel('invoice.number_format')
                        ->setOptions([
                            'help' => 'allowed_replacer',
                            'help_translation_parameters' => [
                                '%replacer%' => '{Y}, {y}, {M}, {m}, {D}, {d}, {date}, {cc}, {ccy}, {ccm}, {ccd}, {cu}, {cuy}, {cum}, {cud}, {ustaff}, {uid}, {c}, {cy}, {cm}, {cd}, {cname}, {cnumber}'
                            ]
                        ])
                        ->setRequired(true)
                        ->setType(TextType::class)
                        ->setConstraints([new NotBlank()])
                        ->setTranslationDomain('system-configuration'),
                ]),
            $authentication,
            (new SystemConfigurationModel('customer'))
                ->setConfiguration([
                    (new Configuration('defaults.customer.timezone'))
                        ->setLabel('timezone')
                        ->setType(TimezoneType::class)
                        ->setValue(date_default_timezone_get())
                        ->setOptions(['help' => 'default_value_new']),
                    (new Configuration('defaults.customer.country'))
                        ->setLabel('country')
                        ->setType(CountryType::class)
                        ->setOptions(['help' => 'default_value_new']),
                    (new Configuration('defaults.customer.currency'))
                        ->setLabel('currency')
                        ->setType(CurrencyType::class)
                        ->setOptions(['help' => 'default_value_new']),
                    (new Configuration('customer.choice_pattern'))
                        ->setLabel('choice_pattern')
                        ->setType(CustomerTypePatternType::class),
                    (new Configuration('customer.number_format'))
                        ->setLabel('customer.number_format')
                        ->setOptions(['help' => 'allowed_replacer', 'help_translation_parameters' => ['%replacer%' => '{cc}']])
                        ->setRequired(true)
                        ->setType(TextType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('customer.rules.allow_duplicate_number'))
                        ->setLabel('allow_duplicate_number')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel('project'))
                ->setConfiguration([
                    (new Configuration('project.choice_pattern'))
                        ->setLabel('choice_pattern')
                        ->setType(ProjectTypePatternType::class),
                    (new Configuration('project.copy_teams_on_create'))
                        ->setLabel('copy_teams_on_create')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('project.number_format'))
                        ->setLabel('project.number_format')
                        ->setOptions(['help' => 'allowed_replacer', 'help_translation_parameters' => ['%replacer%' => '{pc}']])
                        ->setRequired(false)
                        ->setType(TextType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('project.allow_duplicate_number'))
                        ->setLabel('allow_duplicate_number')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel('activity'))
                ->setConfiguration([
                    (new Configuration('activity.choice_pattern'))
                        ->setLabel('choice_pattern')
                        ->setType(ActivityTypePatternType::class),
                    (new Configuration('activity.number_format'))
                        ->setLabel('activity.number_format')
                        ->setOptions(['help' => 'allowed_replacer', 'help_translation_parameters' => ['%replacer%' => '{ac}']])
                        ->setRequired(false)
                        ->setType(TextType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('activity.allow_duplicate_number'))
                        ->setLabel('allow_duplicate_number')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    // TODO see DependencyInjection/Configuration::getActivityNode()
                    /*
                    (new Configuration('activity.allow_inline_create'))
                        ->setLabel('activity.allow_inline_create')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'), */
                ]),
            (new SystemConfigurationModel('user'))
                ->setConfiguration([
                    (new Configuration('defaults.user.timezone'))
                        ->setLabel('timezone')
                        ->setType(TimezoneType::class)
                        ->setValue(date_default_timezone_get())
                        ->setOptions(['help' => 'default_value_new']),
                    (new Configuration('defaults.user.language'))
                        ->setLabel('language')
                        ->setType(UserLanguageType::class)
                        ->setOptions(['help' => 'default_value_new']),
                    (new Configuration('defaults.user.theme'))
                        ->setLabel('skin')
                        ->setType(SkinType::class)
                        ->setOptions(['help' => 'default_value_new']),
                    (new Configuration('defaults.user.currency'))
                        ->setLabel('currency')
                        ->setType(CurrencyType::class),
                    (new Configuration('theme.avatar_url'))
                        ->setRequired(false)
                        ->setLabel('theme.avatar_url')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel('theme'))
                ->setConfiguration([
                    (new Configuration('timesheet.markdown_content'))
                        ->setLabel('theme.markdown_content')
                        ->setType(YesNoType::class)
                        ->setTranslationDomain('system-configuration'),
                    (new Configuration('theme.color_choices'))
                        ->setRequired(false)
                        ->setLabel('theme.color_choices')
                        ->setType(ArrayToCommaStringType::class)
                        ->setOptions(['help' => 'help.theme.color_choices'])
                        ->setConstraints([new ColorChoices()])
                        ->setTranslationDomain('system-configuration'),
                ]),
            (new SystemConfigurationModel('calendar'))
                ->setTranslation('calendar')
                ->setTranslationDomain('messages')
                ->setConfiguration([
                    (new Configuration('calendar.week_numbers'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(YesNoType::class),
                    (new Configuration('calendar.weekends'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(YesNoType::class),
                    (new Configuration('calendar.businessHours.begin'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(DayTimeType::class)
                        ->setConstraints([new NotBlank(), new TimeFormat()]),
                    (new Configuration('calendar.businessHours.end'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(DayTimeType::class)
                        ->setConstraints([new NotBlank(), new TimeFormat()]),
                    (new Configuration('calendar.visibleHours.begin'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(DayTimeType::class)
                        ->setConstraints([new NotBlank(), new TimeFormat()]),
                    (new Configuration('calendar.visibleHours.end'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(DayTimeType::class)
                        ->setConstraints([new NotBlank(), new TimeFormat()]),
                    (new Configuration('calendar.slot_duration'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(TextType::class)
                        ->setConstraints([new Regex(['pattern' => '/[0-2]{1}[0-9]{1}:[0-9]{2}:[0-9]{2}/']), new NotNull()]),
                    (new Configuration('calendar.dragdrop_amount'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(IntegerType::class)
                        ->setConstraints([new Range(['min' => 0, 'max' => 20]), new NotNull()]),
                    (new Configuration('calendar.dragdrop_data'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(YesNoType::class),
                    (new Configuration('calendar.title_pattern'))
                        ->setTranslationDomain('system-configuration')
                        ->setType(CalendarTitlePatternType::class),
                ]),
            (new SystemConfigurationModel('branding'))
                ->setConfiguration([
                    (new Configuration('theme.branding.logo'))
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(TextType::class),
                    (new Configuration('theme.branding.company'))
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(TextType::class),
                    (new Configuration('company.financial_year'))
                        ->setTranslationDomain('system-configuration')
                        ->setRequired(false)
                        ->setType(DatePickerType::class)
                        ->setOptions(['input' => 'string']),
                ]),
        ];
    }
}
