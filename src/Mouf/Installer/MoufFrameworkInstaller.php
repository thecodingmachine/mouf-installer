<?php 
namespace Mouf\Installer;

use Composer\Installer;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Factory;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * This class is in charge of handling the installation of the Mouf framework in composer.
 * The mouf framework has a special type "mouf-framework" in composer.json,
 * This class will be called to handle specific actions.
 * In particular, it will run composer on composer-mouf.json.
 * 
 * @author David Négrier
 */
class MoufFrameworkInstaller extends LibraryInstaller {
	
	/**
	 * This variable is set to true if we are in the process of installing mouf, using the
	 * MoufFrameworkInstaller. This is useful to disable the install process for Mouf inner packages. 
	 *  
	 * @var bool
	 */
	private static $isRunningMoufFrameworkInstaller = false;
	
	/**
	 * {@inheritDoc}
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
		parent::update($repo, $initial, $target);
		
		$this->installMouf();
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::install($repo, $package);
		
		$this->installMouf();
	}
	
	private function installMouf() {
		self::$isRunningMoufFrameworkInstaller = true;
		
		$oldWorkingDirectory = getcwd();
		chdir("vendor/mouf/mouf");
		
		// Now, let's try to run Composer recursively on composer-mouf.json...
		$composer = Factory::create($this->io, 'composer-mouf.json');
		$install = Installer::create($this->io, $composer);
		
		// Let's get some speed by optimizing Mouf's autoloader... always.
		$install->setOptimizeAutoloader(true);
		
		/*$install
		 ->setDryRun($input->getOption('dry-run'))
		->setVerbose($input->getOption('verbose'))
		->setPreferSource($input->getOption('prefer-source'))
		->setPreferDist($input->getOption('prefer-dist'))
		->setDevMode($input->getOption('dev'))
		->setRunScripts(!$input->getOption('no-scripts'))
		;
		
		if ($input->getOption('no-custom-installers')) {
		$install->disableCustomInstallers();
		}*/
		
		$result = $install->run();
		
		chdir($oldWorkingDirectory);
		
		self::$isRunningMoufFrameworkInstaller = false;
		
		// The $result value has changed in Composer during development.
		// In earlier version, "false" meant probleam
		// Now, 0 means "OK".
		// Check disabled because we cannot rely on Composer on this one.
		/*if (!$result) {
			throw new \Exception("An error occured while running Mouf2 installer.");
		}*/
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::uninstall($repo, $package);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function supports($packageType)
	{
		return 'mouf-framework' === $packageType;
	}
	
	/**
	 * Returns true if we are in the process of installing mouf, using the
	 * MoufFrameworkInstaller. This is useful to disable the install process for Mouf inner packages. 
	 */
	public static function getIsRunningMoufFrameworkInstaller() {
		return self::$isRunningMoufFrameworkInstaller;
	}
}