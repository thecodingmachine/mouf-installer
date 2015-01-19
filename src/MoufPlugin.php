<?php
namespace Mouf\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;
use Composer\Util\Filesystem;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Plugin\PluginInterface;

/**
 * RootContainer Installer for Composer.
 * (based on RobLoach's code for ComponentInstaller)
 */
class MoufPlugin implements PluginInterface
{

    public function activate(Composer $composer, IOInterface $io)
    {
    	$moufFrameworkInstaller = new MoufFrameworkInstaller($io, $composer);
    	$composer->getInstallationManager()->addInstaller($moufFrameworkInstaller);
    	
    	$moufLibraryInstaller = new MoufLibraryInstaller($io, $composer);
    	$composer->getInstallationManager()->addInstaller($moufLibraryInstaller);
    	
    	// Now, let's register a script that saves the PHP binary path.
        $rootPackage = $composer->getPackage();
        if (isset($rootPackage)) {
            // Ensure we get the root package rather than its alias.
            while ($rootPackage instanceof AliasPackage) {
                $rootPackage = $rootPackage->getAliasOf();
            }

            // Make sure the root package can override the available scripts.
            if (method_exists($rootPackage, 'setScripts')) {
                $scripts = $rootPackage->getScripts();
                // Act on the "post-autoload-dump" command so that we can act on all
                // the installed packages.
                $scripts['post-autoload-dump']['rootcontainer-installer'] = 'Mouf\\Installer\\MoufPlugin::postAutoloadDump';
                $rootPackage->setScripts($scripts);
            }
        }
    }

    /**
     * Script callback; Acted on after the autoloader is dumped.
     */
    public static function postAutoloadDump(Event $event)
    {
        // Retrieve basic information about the environment and present a
        // message to the user.
        /*$composer = $event->getComposer();
        $io = $event->getIO();
        $io->write('<info>Compiling containers list</info>');*/
    }
}
