<?php

namespace IbexaLogsUi\Bundle\EventSubscriber;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConfigureMenuEvent::MAIN_MENU => 'onConfigureMenu'];
    }

    public function onConfigureMenu(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();
        if (!isset($menu[MainMenuBuilder::ITEM_ADMIN])) {
            return;
        }

        $menu[MainMenuBuilder::ITEM_ADMIN]->addChild('logs_ui', [
            'label' => 'logs_ui.menu.label',
            'route' => 'ibexa_logs_ui_index',
        ]);
    }
}
