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
		if (isset($extra['mouf']['install'])) {
			
			if (!defined('ROOT_PATH')) {
				define('ROOT_PATH', getcwd().DIRECTORY_SEPARATOR);
			}
			
			$multiStepActionService = new MultiStepActionService();
			
			$installSteps = $extra['mouf']['install'];
			if ($installSteps) {
				foreach ($installSteps as $installStep) {
					if ($installStep['type'] == 'file') {
						
						// Are we in selfedit or not? Let's define this using the ROOT_PATH.
						$selfedit = !file_exists($rootPath.'vendor/mouf/mouf');
						
						if ($selfedit) {
							$multiStepActionService->addAction("redirectAction", array(
									"packageName"=>$package->getPrettyName(),
									"redirectUrl"=>"vendor/".$package->getName()."/".$installStep['file']));
						} else {
							$multiStepActionService->addAction("redirectAction", array(
									"packageName"=>$package->getPrettyName(),
									"redirectUrl"=>"../../".$package->getName()."/".$installStep['file']));
						}
					} elseif ($installStep['type'] == 'url') {
						$multiStepActionService->addAction("redirectAction", array(
								"packageName"=>$package->getPrettyName(),
								"redirectUrl"=>$installStep['url']));
					} else {
						throw new Exception("Unknown type during install process.");
					}
				}
			}
				
			$this->io->write("This package needs to be installed. Start your navigator and browse to Mouf UI to install it.");
		}
		// FIXME: rewrite MoufUI.
		
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