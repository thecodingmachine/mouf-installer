<?php
namespace Mouf\Installer;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Plugin\PluginInterface;
use Composer\Repository\CompositeRepository;
use Composer\Package\CompletePackage;
use Composer\Package\RootPackage;
use Composer\Json\JsonFile;
use Composer\Script\ScriptEvents;
use Composer\EventDispatcher\EventSubscriberInterface;

/**
 * RootContainer Installer for Composer.
 * (based on RobLoach's code for ComponentInstaller)
 */
class MoufPlugin implements PluginInterface, EventSubscriberInterface {

    public function activate(Composer $composer, IOInterface $io) {
        $moufFrameworkInstaller = new MoufFrameworkInstaller($io, $composer);
        $composer->getInstallationManager()
            ->addInstaller($moufFrameworkInstaller);

        $moufLibraryInstaller = new MoufLibraryInstaller($io, $composer);
        $composer->getInstallationManager()
            ->addInstaller($moufLibraryInstaller);

    }

    /**
     * Let's register the dependencies update events.
     *
     * @return array
     */
    public static function getSubscribedEvents() {
        return array(
            ScriptEvents::POST_INSTALL_CMD => array(
                array('postInstall', 0)
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('postUpdate', 0)
            ),
        );
    }

    /**
     * Script callback; Acted on after install.
     */
    public function postInstall(Event $event) {
        self::purgeGeneratedContainer($event);
    }

    /**
     * Script callback; Acted on after update.
     */
    public function postUpdate(Event $event) {
        self::purgeGeneratedContainer($event);
    }

    /**
     * Script callback; Acted on after the autoloader is dumped.
     *
     * @param Event $event
     * @param string $action update or install
     * @throws \Exception
     */
    private static function purgeGeneratedContainer(Event $event) {
        $io = $event->getIO();
        $io->write('Purging Mouf compiled container');
        if (file_exists('mouf/no_commit/modificationTimes.php')) {
            unlink('mouf/no_commit/modificationTimes.php');
        }
    }
}
