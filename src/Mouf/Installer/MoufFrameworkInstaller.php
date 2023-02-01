<?php
namespace Mouf\Installer;

use Composer\Installer;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Factory;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

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
	 * MoufFrameworkInstaller. This is useful to disable the installation process for Mouf inner packages.
	 *
	 * @var bool
	 */
	private static $isRunningMoufFrameworkInstaller = false;

	/**
	 * {@inheritDoc}
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
        $promise = parent::update($repo, $initial, $target);
        if (!$promise instanceof PromiseInterface) {
            $promise = resolve(null);
        }

        return $promise->then(function () {
            $this->installMouf();
        });
	}

	/**
	 * {@inheritDoc}
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		$promise = parent::install($repo, $package);
        if (!$promise instanceof PromiseInterface) {
            $promise = resolve(null);
        }

		return $promise->then(function () {
            $this->installMouf();
        });
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

		$result = $install->run();

		chdir($oldWorkingDirectory);

		self::$isRunningMoufFrameworkInstaller = false;

		if ($result !== Installer::ERROR_NONE) {
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

	/**
	 * Returns true if we are in the process of installing mouf, using the
	 * MoufFrameworkInstaller. This is useful to disable the install process for Mouf inner packages.
	 */
	public static function getIsRunningMoufFrameworkInstaller() {
		return self::$isRunningMoufFrameworkInstaller;
	}
}
