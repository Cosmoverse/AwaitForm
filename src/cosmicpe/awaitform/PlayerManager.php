<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use Closure;
use Generator;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;
use function spl_object_id;

final class PlayerManager{

	/**
	 * Stores pending form promises: pending[playerId][formObjectId] = Promise
	 * pending[playerId] isset while the player is connected to the server.
	 * Therefore, pending[playerId] can be an empty array.
	 *
	 * @var array<int, array<int, array{Closure(mixed) : void, Closure(AwaitFormException) : void}>>
	 */
	public array $pending = [];

	public function __construct(Plugin $plugin){
		$manager = Server::getInstance()->getPluginManager();
		$manager->registerEvent(PlayerLoginEvent::class, function(PlayerLoginEvent $event) : void{
			$this->pending[$event->getPlayer()->getId()] = [];
		}, EventPriority::LOWEST, $plugin);
		$manager->registerEvent(PlayerQuitEvent::class, function(PlayerQuitEvent $event) : void{
			$pending = $this->pending[$id = $event->getPlayer()->getId()];
			unset($this->pending[$id]);
			foreach($pending as [, $reject]){
				$reject(new AwaitFormException(code: AwaitFormException::ERR_PLAYER_QUIT));
			}
		}, EventPriority::LOWEST, $plugin);
	}

	/**
	 * @template TResponseResult
	 * @param Player $player
	 * @param ResponseProcessor<TResponseResult> $processor
	 * @param array<string, mixed> $request
	 * @param array $tags
	 * @return Generator<mixed, Await::RESOLVE|Await::REJECT, mixed, TResponseResult>
	 */
	public function sendPlayerForm(Player $player, array $request, ResponseProcessor $processor, array $tags = []) : Generator{
		return yield from Await::promise(function(Closure $resolve, Closure $reject) use($player, $request, $processor, $tags) : void{
			isset($this->pending[$id = $player->getId()]) || throw new AwaitFormException(code: AwaitFormException::ERR_PLAYER_QUIT);
			$form = new AwaitForm($request, $tags, $processor);
			$this->pending[$id][spl_object_id($form)] = [$resolve, $reject];
			$player->sendForm($form);
		});
	}
}