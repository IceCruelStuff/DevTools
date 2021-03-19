<?php

namespace DevTools\commands;

use pocketmine\command\CommandSender;
use pocketmine\network\protocol\Info;
use pocketmine\utils\TextFormat;
use DevTools\commands\DevToolsCommand;
use DevTools\DevTools;

class MakeServerCommand extends DevToolsCommand {

	private $plugin;

	public function __construct(DevTools $plugin) {
		parent::__construct("makeserver", $plugin);
		$this->setDescription("Creates a PocketMine-MP.phar file");
		$this->setUsage("/makeserver");
		$this->setPermission("devtools.command.makeserver");
		$this->plugin = $this->getPlugin();
	}

	public function execute(CommandSender $sender, $label, array $args) {
		if (!$this->testPermission($sender)) {
			return;
		}

		$server = $sender->getServer();
		$pharPath = $this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . $server->getName() . "_" . $server->getPocketMineVersion() . ".phar";
		if (file_exists($pharPath)) {
			$sender->sendMessage("Phar file already exists, overwriting...");
			@unlink($pharPath);
		}
		$phar = new \Phar($pharPath);
		$phar->setMetadata([
			"name" => $server->getName(),
			"version" => $server->getPocketMineVersion(),
			"api" => $server->getApiVersion(),
			"minecraft" => $server->getVersion(),
			"protocol" => Info::CURRENT_PROTOCOL,
			"creationDate" => time()
		]);
		$phar->setStub('<?php define("pocketmine\\\\PATH", "phar://". __FILE__ ."/"); require_once("phar://". __FILE__ ."/src/pocketmine/PocketMine.php");  __HALT_COMPILER();');
		$phar->setSignatureAlgorithm(\Phar::SHA1);
		$phar->startBuffering();

		$filePath = substr(\pocketmine\PATH, 0, 7) === "phar://" ? \pocketmine\PATH : realpath(\pocketmine\PATH) . "/";
		$filePath = rtrim(str_replace("\\", "/", $filePath), "/") . "/";
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "src")) as $file) {
			$path = ltrim(str_replace(["\\", $filePath], ["/", ""], $file), "/");
			if ($path{0} === "." || strpos($path, "/.") !== false || substr($path, 0, 4) !== "src/") {
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

		$sender->sendMessage($server->getName() . " " . $server->getPocketMineVersion() . " Phar file has been created on " . $pharPath);

		return true;
	}

}
