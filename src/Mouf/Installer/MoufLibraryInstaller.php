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
		
		// Rewrite MoufUI.
		$moufUIFileWriter = new MoufUIFileWritter($this->composer);
		$moufUIFileWriter->writeMoufUI();
	}
	
	private $multiStepActionService;
	
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
			
			$this->multiStepActionService = new MultiStepActionService();
			
			$installSteps = $extra['mouf']['install'];
			if (!is_array($installSteps)) {
				$this->io->write("Error while installing package in Mouf. The install parameter in composer.json (extra->mouf->install) should be an array of files/url to install.");
				return;
			}
			
			if ($installSteps) {
				if (self::isAssoc($installSteps)) {
					// If this is directly an associative array (instead of a numerical array of associative arrays)
					$this->handleInstallStep($installSteps, $package);
				}
				
				foreach ($installSteps as $installStep) {
					$this->handleInstallStep($installStep, $package);
				}
			}
			
			$this->io->write("This package needs to be installed. Start your navigator and browse to Mouf UI to install it.");
		}
		
		// Rewrite MoufUI.
		$moufUIFileWriter = new MoufUIFileWritter($this->composer);
		$moufUIFileWriter->writeMoufUI();
		
	}
	
	private function handleInstallStep($installStep, $package) {
		
		if (!is_array($installStep)) {
			$this->io->write("Error while installing package in Mouf. The install parameter in composer.json (extra->mouf->install) should be an array of files/url to install (or a single install descriptor).");
			return;
		}
		if (!isset($installStep['type'])) {
			$this->io->write("Warning! In composer.json, no type found for install file/url.");
			return;
		}
		if ($installStep['type'] == 'file') {
		
			// Are we in selfedit or not? Let's define this using the ROOT_PATH.
			// If ROOT_PATH ends with vendor/mouf/mouf, then yes, we are in selfedit.
			$rootPath = realpath(ROOT_PATH);
			$selfedit = false;
			if (basename($rootPath) == "mouf") {
				$rootPathMinus1 = dirname($rootPath);
		
				if (basename($rootPathMinus1) == "mouf") {
					$rootPathMinus2 = dirname($rootPathMinus1);
		
					if (basename($rootPathMinus2) == "vendor") {
						$selfedit = true;
					}
				}
			}
		
			if ($selfedit) {
				$this->multiStepActionService->addAction("redirectAction", array(
						"packageName"=>$package->getPrettyName(),
						"redirectUrl"=>"vendor/".$package->getName()."/".$installStep['file']));
			} else {
				$this->multiStepActionService->addAction("redirectAction", array(
						"packageName"=>$package->getPrettyName(),
						"redirectUrl"=>"../../".$package->getName()."/".$installStep['file']));
			}
		} elseif ($installStep['type'] == 'url') {
			$this->multiStepActionService->addAction("redirectAction", array(
					"packageName"=>$package->getPrettyName(),
					"redirectUrl"=>$installStep['url']));
		} else {
			throw new \Exception("Unknown type during install process.");
		}
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
		return 'mouf-library' === $packageType;
	}
	
	/**
	 * Returns if an array is associative or not.
	 *  
	 * @param array $arr
	 * @return boolean
	 */
	private static function isAssoc($arr)
	{
	    return array_keys($arr) !== range(0, count($arr) - 1);
	}
}