<?php

namespace DevTools\commands;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use DevTools\commands\DevToolsCommand;
use DevTools\DevTools;

class MakePluginCommand extends DevToolsCommand {

	private $plugin;

	public function __construct(DevTools $plugin) {
		parent::__construct("makeplugin", $plugin);
		$this->setDescription("Creates a Phar plugin from one in source code form");
		$this->setUsage("/makeplugin <pluginName>");
		$this->setPermission("devtools.command.makeplugin");
		$this->plugin = $this->getPlugin();
	}

	public function execute(CommandSender $sender, $label, array $args) {
		if (!$this->testPermission($sender)) {
			return;
		}

		if (isset($args[0]) && $args[0] === "FolderPluginLoader") {
			$pharPath = $this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . "FolderPluginLoader.phar";
			if (file_exists($pharPath)) {
				$sender->sendMessage("Phar plugin already exists, overwriting...");
				@unlink($pharPath);
			}
			$phar = new \Phar($pharPath);
			$phar->setMetadata([
				"name" => "FolderPluginLoader",
				"version" => "1.0.0",
				"main" => "FolderPluginLoader\\Main",
				"api" => ["1.0.0"],
				"depend" => [],
				"description" => "Loader of folder plugins",
				"authors" => ["PocketMine Team"],
				"website" => "https://github.com/PocketMine/DevTools",
				"creationDate" => time()
			]);
			$phar->setStub('<?php __HALT_COMPILER();');
			$phar->setSignatureAlgorithm(\Phar::SHA1);
			$phar->startBuffering();
			$phar->addFromString("plugin.yml", "name: FolderPluginLoader\nversion: 1.0.0\nmain: FolderPluginLoader\\Main\napi: [1.0.0]\nload: STARTUP\n");
			$phar->addFile($this->plugin->getFile() . "src/FolderPluginLoader/FolderPluginLoader.php", "src/FolderPluginLoader/FolderPluginLoader.php");
			$phar->addFile($this->plugin->getFile() . "src/FolderPluginLoader/Main.php", "src/FolderPluginLoader/Main.php");
			foreach ($phar as $file => $finfo) {
				if ($finfo->getSize() > (1024 * 512)) {
					$finfo->compress(\Phar::GZ);
				}
			}
			$phar->stopBuffering();
			$sender->sendMessage("Folder plugin loader has been created on " . $pharPath);
			return true;
		} else {
			$pluginName = trim(implode(" ", $args));
			if (($pluginName === "") || !(($plugin = Server::getInstance()->getPluginManager()->getPlugin($pluginName)) instanceof Plugin)) {
				$sender->sendMessage(TextFormat::RED . "Invalid plugin name, check the name case.");
				return true;
			}
			$description = $plugin->getDescription();

			if (!($plugin->getPluginLoader() instanceof FolderPluginLoader)) {
				$sender->sendMessage(TextFormat::RED . "Plugin " . $description->getName() . " is not in folder structure.");
				return true;
			}

			$pharPath = $this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . $description->getName() . "_v" . $description->getVersion() . ".phar";
			if (file_exists($pharPath)) {
				$sender->sendMessage("Phar plugin already exists, overwriting...");
				@unlink($pharPath);
			}
			$phar = new \Phar($pharPath);
			$phar->setMetadata([
				"name" => $description->getName(),
				"version" => $description->getVersion(),
				"main" => $description->getMain(),
				"api" => $description->getCompatibleApis(),
				"depend" => $description->getDepend(),
				"description" => $description->getDescription(),
				"authors" => $description->getAuthors(),
				"website" => $description->getWebsite(),
				"creationDate" => time()
			]);
			if ($description->getName() === "DevTools") {
				$phar->setStub('<?php require("phar://". __FILE__ ."/src/DevTools/ConsoleScript.php"); __HALT_COMPILER();');
			} else {
				$phar->setStub('<?php echo "PocketMine-MP plugin ' . $description->getName() . ' v' . $description->getVersion() . '\nThis file has been generated using DevTools v' . $this->getDescription()->getVersion() . ' at ' . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
			}
			$phar->setSignatureAlgorithm(\Phar::SHA1);
			$reflection = new \ReflectionClass("pocketmine\\plugin\\PluginBase");
			$file = $reflection->getProperty("file");
			$file->setAccessible(true);
			$filePath = rtrim(str_replace("\\", "/", $file->getValue($plugin)), "/") . "/";
			$phar->startBuffering();
			foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath)) as $file) {
				$path = ltrim(str_replace(["\\", $filePath], ["/", ""], $file), "/");
				if ($path{0} === "." || strpos($path, "/.") !== false) {
					continue;
				}
				$phar->addFile($file, $path);
				$sender->sendMessage("[DevTools] Adding $path");
			}

			foreach ($phar as $file => $finfo) {
				if ($finfo->getSize() > (1024 * 512)) {
					$finfo->compress(\Phar::GZ);
				}
			}
			$phar->stopBuffering();
			$sender->sendMessage("Phar plugin " . $description->getName() . " v" . $description->getVersion() . " has been created on " . $pharPath);
			return true;
		}
	}

}
