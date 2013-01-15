<?php 
namespace Mouf\Installer;

use Composer\Repository\CompositeRepository;

use Composer\Composer;

/**
 * This class is in charge of writting the MoufUI file.
 * The MoufUI.php file contains all the files that are package dependant, but that should be loaded
 * by the Mouf user interface. 
 * 
 * @author David Négrier
 */
class MoufUIFileWritter {
	
	protected $composer;
	
	/**
	 * Constructs the MoufUIFileWritter.
	 * 
	 * @param Composer $composer
	 * @param bool $selfedit
	 */
	public function __construct(Composer $composer) {
		$this->composer = $composer;
	}
	
	/**
	 * Rewrites the file.
	 */
	public function writeMoufUI() {
		$dirPath = getcwd()."/mouf";
		if (!file_exists($dirPath)) {
			if (!is_writable(getcwd())) {
				throw new \Exception("Error, unable to create directory ".$dirPath);
			}
			mkdir($dirPath, 0777, true);		
		}
		
		$filePath = getcwd()."/mouf/MoufUI.php";

		if ((file_exists($filePath) && !is_writable($filePath)) || (!file_exists($filePath) && !is_writable(dirname($filePath)))) {
			throw new \Exception("Error, unable to write file ".$filePath);
		}
		
		$adminFiles = $this->getAdminFiles();
		
		$fp = fopen($filePath, "w");
		fwrite($fp, "<?php\n");
		fwrite($fp, "/**\n");
		fwrite($fp, " * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.\n");
		fwrite($fp, " */\n");
		fwrite($fp, "\n");
		
		fwrite($fp, "// Files declared in the extra:mouf:adminRequire section.\n");
		foreach ($adminFiles as $fileName) {
			fwrite($fp, "require_once __DIR__.'/../vendor/".$fileName."';\n");
		}
		fwrite($fp, "\n");
		
		fwrite($fp, "?>");
		fclose($fp);
	}
	
	/**
	 * Returns the list of files to be included in the MoufUI.
	 * @return array<string>
	 */
	protected function getAdminFiles() {
		
		$localRepos = new CompositeRepository($this->composer->getRepositoryManager()->getLocalRepositories());
		$unorderedPackagesList = $localRepos->getPackages();
		
		$packagesList = PackagesOrderer::reorderPackages($unorderedPackagesList);
	
		$files = array();
	
		foreach ($packagesList as $package) {
			/* @var $package Package */
			$extra = $package->getExtra();
			if (isset($extra["mouf"]["require-admin"])) {
				foreach ($extra["mouf"]["require-admin"] as $adminFile) {
					$files[] = $package->getName().DIRECTORY_SEPARATOR.$adminFile;
				}
			}
		}
		return $files;
	}
}