# Developers

This page is dedicated to all developers who want to contribute to Kimai. You are the best!

# Setting up your environment

All you need is:
- PHP >= 7.1.3
- PHP extension: `PDO-SQLite` enabled

Optional requirement:
- a MySQL/MariaDB instance
- PHP extension: `PDO-MySQL` enabled

You could even test PostgreSQL and tell us how it works!

Read how to [install Kimai v2 in your dev environment](installation.md). 

## Frontend dependencies 

If you want to make changes to CSS / Javascripts, you need:

- [NodeJS](https://www.npmjs.com/)
- [Yarn Package Manager](https://yarnpkg.com/en/)
- [Webpack](https://webpack.js.org/)
- [Webpack Encore](https://github.com/symfony/webpack-encore)

Please [install Yarn for your OS](https://yarnpkg.com/lang/en/docs/install/) and then:

```bash
yarn install
```

To rebuild all assets you have to execute:
```bash
yarn run prod
```

You can find more information at:

- https://symfony.com/doc/current/frontend/encore/installation.html
- https://symfony.com/doc/current/frontend.html

### Rebuilding assets for use in a subdirectory

If you want to run Kimai in a subdirectory, you have to rebuild the frontend assets with a different webpack configuration.
Edit the file [webpack.config.js](../../webpack.config.js) and change `.setPublicPath('/build/')` to your needs.
After that re-compile the assets (see above).

## Running Unit tests

You can run unit and integration tests with built-in commands like that:

 ```bash
bin/console kimai:test-unit
bin/console kimai:test-integration
```

Or you simply run all tests with: 
```bash
bin/phpunit
```

## Check your code styles

You can run the code sniffer with a built-in command like that:

 ```bash
bin/console kimai:phpcs
```

You can also automatically fix the violations by running: 

 ```bash
bin/console kimai:phpcs --fix
```

Be aware that this command will modify all files with violations in the directories `src/` and `tests/`, so its a good idea to commit first.

Our code-styles are configured in [.php_cs.dist](../../.php_cs.dist).

## Translations 

Read more about [languages and translations](translations.md).

## Extending the navigation bar

If you want to add your own entries in the navigation bar, you can subscribe to these events:

- `App\Event\ConfigureMainMenuEvent::CONFIGURE`
- `App\Event\ConfigureAdminMenuEvent::CONFIGURE`

And that's how to use it:

```php
use App\Event\ConfigureMainMenuEvent;
use App\Event\ConfigureAdminMenuEvent;
use Avanzu\AdminThemeBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::CONFIGURE => ['onMainMenuConfigure', 100],
            ConfigureAdminMenuEvent::CONFIGURE => ['onAdminMenuConfigure', 100],
        ];
    }
    
    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $event->getMenu()->addItem(
            new MenuItemModel('timesheet', 'menu.timesheet', 'timesheet', [], 'fa fa-clock-o')
        );
    }

    public function onAdminMenuConfigure(ConfigureAdminMenuEvent $event)
    {
        $event->getAdminMenu()->addChild(
            new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], 'fa fa-clock-o')
        );
    }    
}
```
For more details check the [official menu subscriber](../../src/EventSubscriber/MenuSubscriber.php).

## Extending the dashboard with widgets

If you want to add your own widget rows to the dashboard, you can subscribe to the event:

- `App\Event\DashboardEvent::DASHBOARD`

And that's how to use it:

```php
use App\Event\DashboardEvent;
use App\Model\DashboardSection;
use App\Model\Widget;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyDashboardSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [DashboardEvent::DASHBOARD => ['onDashboardEvent', 200]];
    }
    
    public function onDashboardEvent(DashboardEvent $event)
    {
        $section = new DashboardSection('optional.row.title');
        $widget = new Widget('A title', 100);
        $widget
            ->setIcon('duration')
            ->setColor('purple')
            ->setType(Widget::TYPE_COUNTER)
        ;
        $section->addWidget($widget);
        $event->addSection($section);
    }
}
```
For more details check this [dashboard subscriber](../../src/EventSubscriber/DashboardSubscriber.php).

## Adding tabs to the "control sidebar"

We use twig globals to render the control sidebar tabs, so adding one is as easy as adding a new config entry:

```yaml
admin_lte:
    options:
        control_sidebar:
            # these are the "official" Kimai tabs
            settings:
                icon: "fas fa-cogs"
                controller: 'App\Controller\SidebarController::settingsAction'
            home:
                icon: "fas fa-question-circle"
                template: sidebar/home.html.twig
```

You have to define the `icon` ([read more](theme.md)) to be used and either `controller` action or twig `template`. 
Both follow the default naming syntax and you can link your bundle here instead of the app controller or templates.
You should NOT add them in `config/packages/kimai.yaml` but in your own bundle or the `local.yaml` [config](configurations.md), 
otherwise they might get lost during an update.

## Adding invoice renderer (FIXME)

An invoice renderer is a controller action that receives an instanceof `App\Model\InvoiceModel` and returns a HTML response.
This HTML response is the preview inside for the invoice screen. 

Adding invoice renderer can be achieved by adding keys to the configuration `kimai.invoice.renderer` like this:

```
kimai:
    invoice:
        renderer:
            default: 'App\Controller\InvoiceController::invoiceAction'
```

The name of the renderer must be unique, please prefix it with your vendor or bundle name and make sure it only contains
character as it will be stored in a database column.

## Adding invoice calculator (FIXME)

An invoice calculator is a class extending `App\Invoice\CalculatorInterface` and is responsible for calculating 
invoice rates, taxes and such.   

Adding invoice calculator can be achieved by adding keys to the configuration `kimai.invoice.calculator` like this:

```
kimai:
    invoice:
        calculator:
            default: 'App\Invoice\DefaultCalculator'
```

The name of the calculator must be unique, please prefix it with your vendor or bundle name and make sure it only contains
character as it will be stored in a database column.

## Adding invoice-number generator (FIXME)

An invoice-number generator is a class extending `App\Invoice\NumberGeneratorInterface` and its only task is to generate 
a number for the invoice. In most cases you do not want to mix multiple invoice-number generators througout your invoice templates.   

Adding invoice-number generator can be achieved by adding keys to the configuration `kimai.invoice.number_generator` like this:

```
kimai:
    invoice:
        number_generator:
            default: 'App\Invoice\DateNumberGenerator'
```

The name of the number generator must be unique, please prefix it with your vendor or bundle name and make sure it only contains
character as it will be stored in a database column.

## Adding timesheet calculator

A timesheet calculator will be called on stopped timesheet records. It can rewrite all values but will normally take care 
of the columns `begin`, `end`, `duration` and `rate` but could also be used to apply a default `description`.

Timesheet calculator need to implement the interface `App\Timesheet\CalculatorInterface` and will be automatically tagged 
as `timesheet.calculator` in the service container. They will be found and used *only* if you add them to the service container.

You can apply several rules in the config file [kimai.yaml](../../config/packages/kimai.yaml) for the existing 
`DurationCalculator` and `RateCalculator` implementations.  Please read the [configurations chapter](configurations.md) to find out more. 

The configuration for "rounding rules" can be fetched from the container parameter `kimai.timesheet.rounding`.

The configuration for "hourly-rates multiplication factors" can be fetched from the container parameter `kimai.timesheet.rates`.
