<?php

namespace DevTools\commands;

use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use DevTools\commands\DevToolsCommand;
use DevTools\DevTools;

class CheckPermissionCommand extends DevToolsCommand {

	private $plugin;

	public function __construct(DevTools $plugin) {
		parent::__construct("checkperm", $plugin);
		$this->setDescription("Checks a permission value for the current sender, or a player");
		$this->setUsage("/checkperm <node> [playerName]");
		$this->setAliases(["checkpermission"]);
		$this->setPermission("devtools.command.checkperm");
		$this->plugin = $this->getPlugin();
	}

	public function execute(CommandSender $sender, $label, array $args) {
		if (!$this->testPermission($sender)) {
			return;
		}

		$target = $sender;
		if (!isset($args[0])) {
			return false;
		}
		$node = strtolower($args[0]);
		if (isset($args[1])) {
			$player = $this->plugin->getServer()->getPlayer($args[1]);
			if ($player instanceof Player) {
				$target = $player;
			} else {
				return false;
			}
		}
		if (($target !== $sender) && !$sender->hasPermission("devtools.command.checkperm.other")) {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to check other players");
			return true;
		} else {
			$sender->sendMessage(TextFormat::GREEN . "---- " . TextFormat::WHITE . "Permission node " . $node . TextFormat::GREEN . " ----");
			$perm = $this->plugin->getServer()->getPluginManager()->getPermission($node);
			if ($perm instanceof Permission) {
				$desc = TextFormat::GOLD . "Description: " . TextFormat::WHITE . $perm->getDescription() . "\n";
				$desc .= TextFormat::GOLD . "Default: " . TextFormat::WHITE . $perm->getDefault() . "\n";
				$children = "";
				foreach ($perm->getChildren() as $name => $true) {
					$children .= $name . ", ";
				}
				$desc .= TextFormat::GOLD . "Children: " . TextFormat::WHITE . substr($children, 0, -2) . "\n";
			} else {
				$desc = TextFormat::RED . "Permission does not exist\n";
				$desc .= TextFormat::GOLD . "Default: " . TextFormat::WHITE . Permission::$DEFAULT_PERMISSION . "\n";
			}
			$sender->sendMessage($desc);
			$sender->sendMessage(TextFormat::YELLOW . $target->getName() . TextFormat::WHITE . " has it set to " . ($target->hasPermission($node) === true ? TextFormat::GREEN . "true" : TextFormat::RED . "false"));
			return true;
		}
	}

}
