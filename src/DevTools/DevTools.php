<?php

/*
 * DevTools plugin for PocketMine-MP
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/DevTools>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

namespace DevTools;

use DevTools\commands\CheckPermissionCommand;
use DevTools\commands\ExtractPluginCommand;
use DevTools\commands\MakePluginCommand;
use DevTools\commands\MakeServerCommand;
use FolderPluginLoader\FolderPluginLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\network\protocol\Info;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class DevTools extends PluginBase implements CommandExecutor {

	public function onEnable() {
		$this->getServer()->getPluginManager()->addPermission(new Permission("devtools.command.makeplugin", "Allows the creation of Phar plugins", Permission::DEFAULT_OP));
		$this->getServer()->getPluginManager()->addPermission(new Permission("devtools.command.extractplugin", "Allows the extraction of Phar plugins", Permission::DEFAULT_OP));
		$this->getServer()->getPluginManager()->addPermission(new Permission("devtools.command.makeserver", "Allows the creation of a PocketMine-MP.phar file", Permission::DEFAULT_OP));
		$this->getServer()->getPluginManager()->addPermission(new Permission("devtools.command.checkperm", "Allows checking a permission value", Permission::DEFAULT_TRUE));
		$this->getServer()->getPluginManager()->addPermission(new Permission("devtools.command.checkperm.other", "Allows checking others permission value", Permission::DEFAULT_OP));

		$this->getServer()->getCommandMap()->register("devtools", new CheckPermissionCommand($this));
		$this->getServer()->getCommandMap()->register("devtools", new ExtractPluginCommand($this));
		$this->getServer()->getCommandMap()->register("devtools", new MakePluginCommand($this));
		$this->getServer()->getCommandMap()->register("devtools", new MakeServerCommand($this));

		@mkdir($this->getDataFolder());

		if (!class_exists("FolderPluginLoader\\FolderPluginLoader", false)) {
			$this->getServer()->getPluginManager()->registerInterface("FolderPluginLoader\\FolderPluginLoader");
			$this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), ["FolderPluginLoader\\FolderPluginLoader"]);
			$this->getLogger()->info("Registered folder plugin loader");
			$this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
		}
	}

}
