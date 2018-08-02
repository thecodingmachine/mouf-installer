<?php

declare(strict_types=1);

namespace Mouf\Installer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Filesystem\Filesystem;
use TheCodingMachine\Discovery\Commands\CommandProvider as DiscoveryCommandProvider;
use TheCodingMachine\Discovery\Commands\DumpCommand;

class MoufPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;
    protected $io;

    /**
     * Apply plugin modifications to Composer.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $moufFrameworkInstaller = new MoufFrameworkInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($moufFrameworkInstaller);
        $moufLibraryInstaller = new MoufLibraryInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($moufLibraryInstaller);
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'beforeDumpAutoload',
        ];
    }

    public function beforeDumpAutoload(Event $event)
    {
        // Plugin has been uninstalled
        if (!file_exists(__FILE__)) {
            return;
        }

        // Let's delete the vendor cache
        $vendorCachePaths = array(__DIR__.'/../../../../../../mouf/no_commit/mouf_vendor_analysis.json'
            , __DIR__.'/../../../../../../mouf/no_commit/vendor_analysis.json');

        foreach ($vendorCachePaths as $vendorCachePath) {
            if (\file_exists($vendorCachePath)) {
                $result = \unlink($vendorCachePath);
                if ($result === false) {
                    throw new \Exception('Unable to delete cache file '.$vendorCachePath);
                }
            }
        }
    }
}
