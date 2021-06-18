<?php


namespace Shopware\Production;


use Composer\Autoload\ClassLoader;
use Composer\IO\NullIO;
use Shopware\Core\Framework\Plugin\Composer\PackageProvider;
use Shopware\Core\Framework\Plugin\Exception\ExceptionCollection;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Shopware\Core\Framework\Plugin\Util\PluginFinder;
use Shopware\Core\Framework\Plugin\Util\VersionSanitizer;

class LoadAllPluginLoader extends KernelPluginLoader
{
    private string $projectDir;

    public function __construct(ClassLoader $classLoader, ?string $pluginDir, string $projectDir)
    {
        parent::__construct($classLoader, $pluginDir);
        $this->projectDir = $projectDir;
    }

    protected function loadPluginInfos(): void
    {
        $pluginFinder = new PluginFinder(new PackageProvider());
        $versionSanitizer = new VersionSanitizer();
        $errors = new ExceptionCollection();

        $plugins = $pluginFinder->findPlugins($this->getPluginDir($this->projectDir), $this->projectDir, $errors, new NullIO());

        foreach ($plugins as $plugin) {
            $info = $plugin->getComposerPackage();
            $version = $versionSanitizer->sanitizePluginVersion($info->getVersion());
            $extra = $info->getExtra();
            $license = $info->getLicense();

            $pluginInfo = [
                'name' => $plugin->getName(),
                'composerName' => $info->getName(),
                'baseClass' => $plugin->getBaseClass(),
                'active' => 1,
                'path' => str_replace($this->projectDir . '/', '', $plugin->getPath()),
                'version' => $version,
                'author' => '', // TODO
                'copyright' => $extra['copyright'] ?? null,
                'license' => implode(', ', $license),
                'iconRaw' => null,
                'autoload' => $info->getAutoload(),
                'managedByComposer' => $plugin->getManagedByComposer(),
            ];

            $this->pluginInfos[] = $pluginInfo;
        }
    }
}
