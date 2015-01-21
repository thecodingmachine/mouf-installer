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
	 * Let's register the harmony dependencies update events.
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
		self::processHarmonyDependencies($event, 'install');
	}

	/**
	 * Script callback; Acted on after update.
	 */
	public function postUpdate(Event $event) {
		self::processHarmonyDependencies($event, 'update');
	}

	/**
	 * Script callback; Acted on after the autoloader is dumped.
	 * 
	 * @param Event $event
	 * @param string $action update or install
	 * @throws \Exception
	 */
	private static function processHarmonyDependencies(Event $event, $action) {
		// Let's trigger EmbeddedComposer.
		$composer = $event->getComposer();
		$io = $event->getIO();
		$io->write('');
		$io->write('Updating Harmony dependencies');
		$io->write('=============================');

		$composerHarmonyFiles = [];

		// Let's start by scanning all packages for a composer-harmony.json file.
		//$localRepos = new CompositeRepository(array($composer->getRepositoryManager()->getLocalRepository()));
		//$packagesList = $localRepos->getPackages();
		$packagesList = $composer->getRepositoryManager()->getLocalRepository()
				->getCanonicalPackages();
		$packagesList[] = $composer->getPackage();

		$globalHarmonyComposer = [];

		foreach ($packagesList as $package) {
			/* @var $package PackageInterface */
			if ($package instanceof CompletePackage) {
				if ($package instanceof RootPackage) {
					$targetDir = "";
				} else {
					$targetDir = "vendor/" . $package->getName() . "/";
				}
				if ($package->getTargetDir()) {
					$targetDir .= $package->getTargetDir() . "/";
				}

				$composerFile = $targetDir . "composer-harmony.json";
				if (file_exists($composerFile) && is_readable($composerFile)) {
					$harmonyData = self::loadComposerHarmonyFile(
							$composerFile, '../../' . $targetDir);
					$globalHarmonyComposer = array_merge_recursive(
							$globalHarmonyComposer, $harmonyData);
				}
			}
		}
		
		// Finally, let's merge the extra.container-interop section of the composer-mouf.json file
		$composerMouf = self::loadComposerHarmonyFile("vendor/mouf/mouf/composer-mouf.json", "");
		$composerMoufSection = [ "extra" => [ "container-interop" => $composerMouf['extra']['container-interop'] ] ];

		$globalHarmonyComposer = array_merge_recursive(
				$globalHarmonyComposer, $composerMoufSection);
		
		$targetHarmonyFile = 'composer-harmony-dependencies.json';

		if (file_exists($targetHarmonyFile) && !is_writable($targetHarmonyFile)) {
			$io
					->write(
							"<error>Error, unable to write file '"
									. $targetHarmonyFile
									. "'. Please check file-permissions.</error>");
			return;
		}

		if (!file_exists($targetHarmonyFile)
				&& !is_writable(dirname($targetHarmonyFile))) {
			$io
					->write(
							"<error>Error, unable to write a file in directory '"
									. dirname($targetHarmonyFile)
									. "'. Please check file-permissions.</error>");
			return;
		}

		if ($globalHarmonyComposer) {
			$result = file_put_contents($targetHarmonyFile,
					json_encode($globalHarmonyComposer,
							JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			if ($result == false) {
				throw new \Exception(
						"An error occured while writing file '"
								. $targetHarmonyFile . "'");
			}
			
			// Run command
			$oldCwd = getcwd();
			chdir('vendor/mouf/mouf');
			$commandLine = PHP_BINARY . " console.php composer:$action";
			passthru($commandLine);
			chdir($oldCwd);
		} else {
			$io->write("<info>No harmony dependencies to $action</info>");
			if (file_exists($targetHarmonyFile)) {
				$result = unlink($targetHarmonyFile);
				if ($result == false) {
					throw new \Exception(
							"An error occured while deleting file '"
							. $targetHarmonyFile . "'");
				}
			}
			if (file_exists($targetHarmonyFile.".lock")) {
				$result = unlink($targetHarmonyFile.".lock");
				if ($result == false) {
					throw new \Exception(
							"An error occured while deleting file '"
							. $targetHarmonyFile . ".lock'");
				}
			}
		}

	}

	/**
	 * Loads a harmony file, returns the array, with autoloads modified to fit the directory. 
	 * 
	 * @param string $composerHarmonyFile
	 */
	private static function loadComposerHarmonyFile($composerHarmonyFile,
			$targetDir) {
		$configJsonFile = new JsonFile($composerHarmonyFile);

		try {
			$configJsonFile->validateSchema(JsonFile::LAX_SCHEMA);
			$localConfig = $configJsonFile->read();
		} catch (ParsingException $e) {
			throw new \Exception(
					"Error while parsing file '" . $composerHarmonyFile . "'",
					0, $e);
		}

		foreach (['autoload', 'autoload-dev'] as $autoloadType) {
			foreach (['psr-4', 'psr-0', 'classmap', 'files'] as $mode) {
				if (isset($localConfig[$autoloadType][$mode])) {
					$localConfig[$autoloadType][$mode] = array_map(
							function ($path) use ($targetDir) {
								if (!is_array($path)) {
									$path = [$path];
								}
								return array_map(
										function ($pathItem) use ($targetDir) {
											return $targetDir . $pathItem;
										}, $path);
							}, $localConfig[$autoloadType][$mode]);
				}
			}
		}
		
		// Let's wrap all container-factory sections into arrays so they get correctly merged.
		if (isset($localConfig["extra"]["container-interop"]["container-factory"])) {
			$factorySection = $localConfig["extra"]["container-interop"]["container-factory"];
			if (!is_array($factorySection) || self::isAssoc($factorySection)) {
				$localConfig["extra"]["container-interop"]["container-factory"] = [ $factorySection ];
			}
		}

		return $localConfig;
	}
	
	/**
	 * Returns if an array is associative or not.
	 *
	 * @param  array   $arr
	 * @return boolean
	 */
	private static function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
