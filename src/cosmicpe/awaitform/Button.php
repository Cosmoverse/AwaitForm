<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

final class Button{

	public const string IMAGE_TYPE_PATH = "path";
	public const string IMAGE_TYPE_URL = "url";

	/**
	 * Creates a button with text and no image.
	 *
	 * @param string $text
	 * @return self
	 */
	public static function simple(string $text) : self{
		return new self(["text" => $text, "image" => null]);
	}

	/**
	 * Creates a button with text and an image path (game resources).
	 *
	 * @param string $text
	 * @param non-empty-string $path
	 * @return self
	 */
	public static function withImagePath(string $text, string $path) : self{
		return new self(["text" => $text, "image" => ["type" => self::IMAGE_TYPE_PATH, "data" => $path]]);
	}

	/**
	 * Creates a button with text and an image URL (online source).
	 *
	 * @param string $text
	 * @param non-empty-string $url
	 * @return self
	 */
	public static function withImageUrl(string $text, string $url) : self{
		return new self(["text" => $text, "image" => ["type" => self::IMAGE_TYPE_URL, "data" => $url]]);
	}

	/**
	 * @param array{text: string, image: array{type: self::IMAGE_TYPE_*, data: non-empty-string}|null} $data
	 */
	private function __construct(
		readonly public array $data
	){}
}