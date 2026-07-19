<?php
declare(strict_types=1);
namespace OCA\Charity\AppInfo;

use OCA\Charity\Listener\AppEnableListener;
use OCP\App\Events\AppEnableEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'charity';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(AppEnableEvent::class, AppEnableListener::class);
    }

    public function boot(IBootContext $context): void {
        // Register hooks here
    }
}
