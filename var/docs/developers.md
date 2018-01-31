# Developers

This page is dedicated to all developers who want to contribute to Kimai. You are the best!

# Setting up your environment

All you need is:
- PHP >= 7.1 
- PHP extension: `PDO-SQLite` enabled

Optional requirement:
- a MySQL/MariaDB instance
- PHP extension: `PDO-MySQL` enabled

You could even test PostgreSQL and tell us how it works!

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
```
./node_modules/.bin/encore dev
```
or
```
./node_modules/.bin/encore production
```

You can find more information at:

- https://symfony.com/doc/current/frontend/encore/installation.html
- https://symfony.com/doc/current/frontend.html

## Extending the navigation bar

If you want to add your own entries in the navigation bar, you can subscribe to these events:

- `App\EventConfigureMainMenuEvent::CONFIGURE`
- `App\ConfigureAdminMenuEvent::CONFIGURE`

And that's how to use it:

```php
use App\Event\ConfigureMainMenuEvent;
use App\Event\ConfigureAdminMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Avanzu\AdminThemeBundle\Model\MenuItemModel;

class MySubscriber implements EventSubscriberInterface
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
For more deatils check the [official menu subscriber](https://github.com/kevinpapst/kimai2/blob/master/src/EventSubscriber/MenuSubscriber.php).

## Adding tabs to the "control sidebar"

We use twig globals to render the control sidebar tabs, so adding one is as easy as adding a new config entry:

```yaml
twig:
    globals:
        kimai_context:
            control_sidebar:
                # these are the official tabs
                settings:
                    icon: gears
                    controller: 'App\Controller\SidebarController::settingsAction'
                home:
                    icon: question-circle-o
                    template: sidebar/home.html.twig

```
You have to define the icon to be used and then one of controller action or template. 
Both follow the default naming syntax and you can easily link your bundle here instead of the official.
You should NOT add them in `config/packages/kimai.yaml` but only in your own bundle config.