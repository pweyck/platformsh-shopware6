<?php declare(strict_types=1);

namespace Shopware\Production;

use Shopware\Core\Framework\App\ActiveAppsLoader;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\BundleConfigGeneratorInterface;
use Shopware\Core\Kernel;
use Shopware\Core\System\Annotation\Concept\ExtensionPattern\Decoratable;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @Decoratable
 */
class BundleConfigGenerator implements BundleConfigGeneratorInterface
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(
        Kernel $kernel
    ) {
        $this->kernel = $kernel;

        $projectDir = $this->kernel->getContainer()->getParameter('kernel.project_dir');
        if (!\is_string($projectDir)) {
            throw new \RuntimeException('Container parameter "kernel.project_dir" needs to be a string');
        }
        $this->projectDir = $projectDir;
    }

    public function getConfig(): array
    {
        return array_merge($this->generatePluginConfigs());
    }

    private function generatePluginConfigs(): array
    {
        $kernelBundles = $this->kernel->getBundles();

        $bundles = [];
        foreach ($kernelBundles as $bundle) {
            // only include shopware bundles
            if (!$bundle instanceof Bundle) {
                continue;
            }

            $path = $bundle->getPath();
            if (mb_strpos($bundle->getPath(), $this->projectDir) === 0) {
                // make relative
                $path = ltrim(mb_substr($path, mb_strlen($this->projectDir)), '/');
            }

            $bundles[$bundle->getName()] = [
                'basePath' => $path . '/',
                'views' => ['Resources/views'],
                'technicalName' => str_replace('_', '-', $bundle->getContainerPrefix()),
                'administration' => [
                    'path' => 'Resources/app/administration/src',
                    'entryFilePath' => $this->getEntryFile($bundle->getPath(), 'Resources/app/administration/src'),
                    'webpack' => $this->getWebpackConfig($bundle->getPath(), 'Resources/app/administration'),
                ],
                'storefront' => [
                    'path' => 'Resources/app/storefront/src',
                    'entryFilePath' => $this->getEntryFile($bundle->getPath(), 'Resources/app/storefront/src'),
                    'webpack' => $this->getWebpackConfig($bundle->getPath(), 'Resources/app/storefront'),
                    'styleFiles' => $this->getStyleFiles($bundle->getName()),
                ],
            ];
        }

        return $bundles;
    }

    private function getEntryFile(string $rootPath, string $componentPath): ?string
    {
        $path = trim($componentPath, '/');
        $absolutePath = $rootPath . '/' . $path;

        return file_exists($absolutePath . '/main.ts') ? $path . '/main.ts'
            : (file_exists($absolutePath . '/main.js') ? $path . '/main.js'
            : null);
    }

    private function getWebpackConfig(string $rootPath, string $componentPath): ?string
    {
        $path = trim($componentPath, '/');
        $absolutePath = $rootPath . '/' . $path;

        if (!file_exists($absolutePath . '/build/webpack.config.js')) {
            return null;
        }

        return $path . '/build/webpack.config.js';
    }

    private function getStyleFiles(string $technicalName): array
    {
        if (!$this->kernel->getContainer()->has('Shopware\Storefront\Theme\StorefrontPluginRegistry')) {
            return [];
        }

        $registry = $this->kernel->getContainer()->get('Shopware\Storefront\Theme\StorefrontPluginRegistry');
        $config = $registry->getConfigurations()->getByTechnicalName($technicalName);

        if (!$config) {
            return [];
        }

        return $config->getStyleFiles()->getFilepaths();
    }
}
