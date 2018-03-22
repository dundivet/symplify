<?php declare(strict_types=1);

namespace Symplify\PackageBuilder\Configuration;

use Symfony\Component\Console\Input\InputInterface;
use Symplify\PackageBuilder\Exception\Configuration\FileNotFoundException;

final class ConfigFilePathHelper
{
    /**
     * @var string[]
     */
    private static $optionNames = ['--config', '-c'];

    /**
     * @var string[]
     */
    private static $configFilePaths = [];

    public static function detectFromInput(string $name, InputInterface $input): void
    {
        $configValue = self::getConfigValue($input);
        if ($configValue === null) {
            return;
        }

        $filePath = self::makeAbsolutePath($configValue);

        if (! file_exists($filePath)) {
            throw new FileNotFoundException(sprintf('File "%s" not found in "%s".', $filePath, $configValue));
        }

        self::$configFilePaths[$name] = $filePath;
    }

    public static function provide(string $name, ?string $configName = null): ?string
    {
        if (isset(self::$configFilePaths[$name])) {
            return self::$configFilePaths[$name];
        }

        $rootConfigPath = getcwd() . DIRECTORY_SEPARATOR . $configName;
        if (is_file($rootConfigPath)) {
            return self::$configFilePaths[$name] = $rootConfigPath;
        }

        return null;
    }

    public static function set(string $name, string $configFilePath): void
    {
        self::$configFilePaths[$name] = $configFilePath;
    }

    public static function makeAbsolutePath(string $relativeFilePath): string
    {
        return preg_match('#/|\\\\|[a-z]:#iA', $relativeFilePath)
            ? $relativeFilePath
            : getcwd() . DIRECTORY_SEPARATOR . $relativeFilePath;
    }

    private static function getConfigValue(InputInterface $input): ?string
    {
        foreach (self::$optionNames as $optionName) {
            if ($input->hasParameterOption($optionName)) {
                return $input->getParameterOption($optionName);
            }
        }

        return null;
    }
}
