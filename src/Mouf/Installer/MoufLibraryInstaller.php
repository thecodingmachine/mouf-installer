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
 * In particular, it will look in "extra": { "install":...} if there are any actions
 * to perform.
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

		$extra = $package->getExtra();
		if (isset($extra['install'])) {
			
			// We need the "ROOT_URL" variable.
			require_once 'config.php';
			define('ROOT_PATH', getcwd().DIRECTORY_SEPARATOR);
			
			$multiStepActionService = new MultiStepActionService();
			
			$installSteps = $extra['install'];
			if ($installSteps) {
				foreach ($installSteps as $installStep) {
					if ($installStep['type'] == 'file') {
						$multiStepActionService->addAction("redirectAction", array(
								"packageName"=>$package->getPrettyName(),
								"redirectUrl"=>ROOT_URL."vendor/".$package->getName()."/".$installStep['file']));
					} elseif ($installStep['type'] == 'url') {
						$multiStepActionService->addAction("redirectAction", array(
								"packageName"=>getPrettyName(),
								"redirectUrl"=>ROOT_URL.$installStep['url']));
					} else {
						throw new Exception("Unknown type during install process.");
					}
				}
			}
				
			$this->io->write("This package needs to be installed. Start your navigator and browse to Mouf UI to install it.");
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
		return 'mouf-library' === $packageType;
	}
}