<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use RuntimeException;

final class AwaitFormException extends RuntimeException{

	/**
	 * Thrown when player sent form data that is incorrectly structured
	 * or contains invalid values.
	 */
	public const int ERR_VALIDATION_FAILED = 100001;

	/**
	 * Thrown when player rejects answering the form. This could be due
	 * to the player closing the form or being "busy".
	 * {@see ModalFormResponsePacket::CANCEL_REASON_CLOSED}
	 * {@see ModalFormResponsePacket::CANCEL_REASON_USER_BUSY}
	 */
	public const int ERR_PLAYER_REJECTED = 100002;

	/**
	 * Thrown when player quits the server and therefore does not give
	 * a response.
	 */
	public const int ERR_PLAYER_QUIT = 100003;
}