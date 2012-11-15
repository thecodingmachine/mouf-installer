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
class MoufLibraryInstaller extends LibraryInstaller {
	
	/**
	 * {@inheritDoc}
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
		parent::update($repo, $initial, $target);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::install($repo, $package);
		
		$oldWorkingDirectory = getcwd();
		chdir("vendor/mouf/mouf");
		
		// Now, let's try to run Composer recursively on composer-mouf.json...
		$io = $this->getIO();
		$composer = Factory::create($io, 'composer-mouf.json');
		$install = Installer::create($io, $composer);
		
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
		
		if (!$result) {
			throw new \Exception("An error occured while running Mouf2 installer.");	
		}
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
}