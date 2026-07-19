<?php
declare(strict_types=1);
namespace OCA\Charity\Listener;

use OCA\Charity\AppInfo\Application;
use OCA\Charity\Setup\AppSetup;
use OCP\App\Events\AppEnableEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AppEnableEvent>
 */
class AppEnableListener implements IEventListener {
	private AppSetup $appSetup;

	public function __construct(AppSetup $appSetup) {
		$this->appSetup = $appSetup;
	}

	public function handle(Event $event): void {
		if ($event instanceof AppEnableEvent && $event->getAppId() === Application::APP_ID) {
			$this->appSetup->run();
		}
	}
}
