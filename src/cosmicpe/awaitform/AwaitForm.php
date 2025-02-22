<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use BadMethodCallException;
use InvalidArgumentException;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use PrefixedLogger;
use ReflectionClass;
use function spl_object_id;

/**
 * @template TResponseResult
 */
final class AwaitForm implements Form{

	private static PlayerManager $player_manager;

	public static function isRegistered() : bool{
		return isset(self::$player_manager);
	}

	public static function register(Plugin $plugin) : void{
		isset(self::$player_manager) && throw new BadMethodCallException(__CLASS__ . " is already registered");
		self::$player_manager = new PlayerManager($plugin);
	}

	private static function playerManagerNotFound() : PlayerManager{
		$class = new ReflectionClass(self::class);
		$logger = new PrefixedLogger(Server::getInstance()->getLogger(), $class->getShortName());
		$logger->warning("Property \$player_manager has not been initialized.");
		$logger->warning("This means you likely forgot to register this library on plugin enable.");
		$logger->warning("You can address this error by updating your plugin's onEnable method as below:");
		$logger->warning("```");
		$logger->warning("use {$class->name};");
		$logger->warning("protected function onEnable() : void{");
		$logger->warning("    if(!{$class->getShortName()}::isRegistered()){");
		$logger->warning("        {$class->getShortName()}::register(\$this);");
		$logger->warning("    }");
		$logger->warning("}");
		$logger->warning("```");
		throw new InvalidArgumentException("Could not find player manager");
	}

	/**
	 * Creates a simple two-button dialogue window.
	 *
	 * @param string $title
	 * @param string $content
	 * @param string $button1
	 * @param string $button2
	 * @return DialogWindow
	 */
	public static function dialog(string $title, string $content, string $button1 = "gui.yes", string $button2 = "gui.no") : DialogWindow{
		return new DialogWindow(self::$player_manager ?? self::playerManagerNotFound(), $title, $content, $button1, $button2);
	}

	/**
	 * Creates a customizable form using various form controls.
	 * Returns user response mapped to respective $content keys.
	 * @param string $title
	 * @param array<TIndex, FormControl> $content
	 * @return FormWindow<TIndex>
	 * @see FormControl for more details.
	 *
	 * @template TIndex of string|int
	 */
	public static function form(string $title, array $content) : FormWindow{
		return new FormWindow(self::$player_manager ?? self::playerManagerNotFound(), $title, $content);
	}

	/**
	 * Creates a multi-button window. Returns index of button corresponding
	 * to user response.
	 *
	 * @template TIndex of string|int
	 * @param string $title
	 * @param string $content
	 * @param array<TIndex, Button> $buttons
	 * @return MenuWindow<TIndex>
	 */
	public static function menu(string $title, string $content, array $buttons) : MenuWindow{
		$values = [];
		foreach($buttons as $index => $button){
			$values[] = [$button, $index];
		}
		return new MenuWindow(self::$player_manager ?? self::playerManagerNotFound(), $title, $content, $values);
	}

	/**
	 * @param array<string, mixed> $request
	 * @param array<string, mixed> $tags
	 * @param ResponseProcessor<TResponseResult> $processor
	 */
	public function __construct(
		readonly public array $request,
		readonly public array $tags,
		readonly public ResponseProcessor $processor
	){}

	public function jsonSerialize() : array{
		return $this->request;
	}

	public function handleResponse(Player $player, mixed $data) : void{
		if(!isset(self::$player_manager->pending[$id = $player->getId()][$fid = spl_object_id($this)])){
			return;
		}
		[$resolve, $reject] = self::$player_manager->pending[$id][$fid];
		unset(self::$player_manager->pending[$id][$fid]);
		if($data === null){
			$reject(new AwaitFormException(code: AwaitFormException::ERR_PLAYER_REJECTED));
			return;
		}
		try{
			$data = $this->processor->process($this->request, $this->tags, $data);
		}catch(InvalidArgumentException $e){
			$reject(new AwaitFormException(message: $e->getMessage(), code: AwaitFormException::ERR_VALIDATION_FAILED));
			throw new FormValidationException($e->getMessage(), $e->getCode(), $e);
		}
		$resolve($data);
	}
}