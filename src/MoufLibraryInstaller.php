<?php 
namespace Mouf\Installer;

use Mouf\Actions\MultiStepActionService;

use Composer\Repository\InstalledRepositoryInterface;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * This class is in charge of handling the installation of Mouf packages in composer.
 * When a package whith the type "mouf-library" (in composer.json) is installed by composer,
 * this class will be called to handle specific actions.
 * In particular, it will look in "extra": { "install":...} and prompt the user to perform installation in Mouf.
 * It will also rewrite the MoufUI file.
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
		
		// Rewrite MoufUI.
		$moufUIFileWriter = new MoufUIFileWritter($this->composer);
		$moufUIFileWriter->writeMoufUI();
	}
	
	private $multiStepActionService;
	private $rootPath;
	
	/**
	 * {@inheritDoc}
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::install($repo, $package);

		// If this package is installed as part of a mouf upgrade/install, let's not run the MoufUI generation.
		// Indeed, it has already been commited by the Mouf developers, no need to regenerate Mouf's MoufUI.
		if (MoufFrameworkInstaller::getIsRunningMoufFrameworkInstaller()) {
			return;
		}
		
		$extra = $package->getExtra();
		if (isset($extra['mouf']['install'])) {
			$this->io->write("This package needs to be installed. Start your navigator and browse to Mouf UI to install it.");
		}
		
		// Rewrite MoufUI.
		$moufUIFileWriter = new MoufUIFileWritter($this->composer);
		$moufUIFileWriter->writeMoufUI();
		
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::uninstall($repo, $package);
		
		// Rewrite MoufUI.
		$moufUIFileWriter = new MoufUIFileWritter($this->composer);
		$moufUIFileWriter->writeMoufUI();
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function supports($packageType)
	{
		// Check if mouf-library is contained in the $packageType variable. It has to be a word in the string
		// but there can be many other words in packageType.
		return preg_match("/\\bmouf-library\\b/", $packageType) === 1;
	}
}