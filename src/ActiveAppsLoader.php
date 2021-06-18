<?php declare(strict_types=1);

namespace Shopware\Production;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Shopware\Core\Framework\App\ActiveAppsLoader as ShopwareActiveAppsLoader;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
class ActiveAppsLoader extends ShopwareActiveAppsLoader
{
    /**
     * @var array|null
     */
    private $activeApps;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        
        $this->connection = $connection;
    }

    public function getActiveApps(): array
    {
        if ($this->activeApps === null) {
            $this->activeApps = $this->loadApps();
        }

        return $this->activeApps;
    }

    public function resetActiveApps(): void
    {
        $this->activeApps = null;
    }

    private function loadApps(): array
    {
        try {
            return $this->connection->executeQuery('
                SELECT `name`, `path`, `author`
                FROM `app`
                WHERE `active` = 1
            ')->fetchAll(FetchMode::ASSOCIATIVE);
        } catch (\Throwable $_) {
        }

        return [];
    }
}
