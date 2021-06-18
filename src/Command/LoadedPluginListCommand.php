<?php declare(strict_types=1);

namespace Shopware\Production\Command;

use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadedPluginListCommand extends Command
{
    protected static $defaultName = 'plugin:loaded';

    private array $pluginInfos;

    public function __construct(array $pluginInfos)
    {
        parent::__construct();
        $this->pluginInfos = $pluginInfos;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Show a list of loaded plugins.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ShopwareStyle($input, $output);
        $io->title('Shopware Plugin Service');

        $pluginTable = [];

        foreach ($this->pluginInfos as $plugin) {
            $pluginTable[] = [
                $plugin['composerName'],
                $plugin['name'],
                $plugin['version'],
            ];
        }

        $io->table(
            ['Plugin', 'Label', 'Version'],
            $pluginTable
        );

        return 0;
    }
}
