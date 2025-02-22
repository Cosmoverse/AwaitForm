<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use InvalidArgumentException;
use function array_search;
use function implode;

/**
 * Creates various form controls to customize a form. Each control has a "label"
 * property that displays some text right above the control. Ideally, this serves
 * the purpose of clarity and context. Multiple controls may share the same label
 * value in a specific form.
 *
 * @see AwaitForm::form()
 */
final class FormControl{

	/**
	 * @param string $label
	 * @param non-empty-list<string> $options
	 * @param int $default
	 * @param array $tags
	 * @return self
	 */
	private static function dropdownGeneric(string $label, array $options, int $default, array $tags = []) : self{
		($default >= 0 && $default < count($options)) || throw new InvalidArgumentException("Default value index '{$default}' does not exist in options list: '" . implode(", ", $options) . "'");
		return new self(["type" => "dropdown", "text" => $label, "options" => $options, "default" => $default], $tags);
	}

	/**
	 * Creates a dropdown from a list of choices ($options). $default is a value in
	 * $options, or null to select the first value in $options by default. Returns
	 * one of the values from $options as response.
	 *
	 * @param string $label
	 * @param non-empty-list<string> $options
	 * @param string|null $default
	 * @return self
	 */
	public static function dropdown(string $label, array $options, ?string $default = null) : self{
		if($default === null){
			$default_index = 0;
		}else{
			$default_index = array_search($default, $options, true);
			$default_index !== false || throw new InvalidArgumentException("Default value '{$default}' does not exist in options list: '" . implode(", ", $options) . "'");
		}
		return self::dropdownGeneric($label, $options, $default_index, ["return" => "value"]);
	}

	/**
	 * Creates a dropdown from a list of choices ($options). $default is an index of
	 * $options. Returns index position of one of the values from $options as response.
	 *
	 * @param string $label
	 * @param non-empty-list<string> $options
	 * @param int $default
	 * @return self
	 */
	public static function dropdownIndex(string $label, array $options, int $default = 0) : self{
		return self::dropdownGeneric($label, $options, $default, ["return" => "key"]);
	}

	/**
	 * Creates a dropdown from a list of choices ($options). $default is a value in
	 * $options, or null to select the first value in $options by default. A value
	 * $mapping is preserved server-side for every value in $options. Returns a value
	 * from $mapping corresponding to the index position in $options of the selected
	 * user response.
	 *
	 * @template TMapping
	 * @param string $label
	 * @param non-empty-list<string> $options
	 * @param non-empty-list<TMapping> $mapping
	 * @param TMapping|null $default
	 * @return self
	 */
	public static function dropdownMap(string $label, array $options, array $mapping, mixed $default = null) : self{
		if($default === null){
			$default_index = 0;
		}else{
			$default_index = array_search($default, $mapping, true);
			$default_index !== false || throw new InvalidArgumentException("Default value '{$default}' does not exist in options list");
		}
		return self::dropdownGeneric($label, $options, $default_index, ["return" => "mapping", "mapping" => $mapping]);
	}

	/**
	 * Creates an input field initialized with a $default value. A non-empty $placeholder
	 * string may be supplied (such as "Enter text...") for display purpose. Returns the
	 * raw value supplied in this field as response.
	 *
	 * @param string $label
	 * @param string $placeholder
	 * @param string $default
	 * @return self
	 */
	public static function input(string $label, string $placeholder = "", string $default = "") : self{
		return new self(["type" => "input", "text" => $label, "placeholder" => $placeholder, "default" => $default]);
	}

	/**
	 * Creates a text box that display some given text.
	 *
	 * @param string $label
	 * @return self
	 */
	public static function label(string $label) : self{
		return new self(["type" => "label", "text" => $label]);
	}

	/**
	 * Creates a numeric slider initialized with a $default value. The user is asked to
	 * pick a value in the specified range in increments of $step. Returns the float
	 * value the slider points at.
	 *
	 * @param string $label
	 * @param float $min
	 * @param float $max
	 * @param float $step
	 * @param float $default
	 * @return self
	 */
	public static function slider(string $label, float $min, float $max, float $step = 0.0, float $default = 0.0) : self{
		return new self(["type" => "slider", "text" => $label, "min" => $min, "max" => $max, "step" => $step, "default" => $default]);
	}

	/**
	 * Creates a choice slider initialized with a $default value. Similar to a dropdown but
	 * the user is presented a slider instead. Returns a value in $steps that the slider
	 * points at.
	 *
	 * @param string $label
	 * @param non-empty-list<string> $steps
	 * @param string|null $default
	 * @return self
	 */
	public static function stepSlider(string $label, array $steps, ?string $default = null) : self{
		if($default === null){
			$default_index = 0;
		}else{
			$default_index = array_search($default, $steps, true);
			$default_index !== false || throw new InvalidArgumentException("Default value '{$default}' does not exist in steps list: '" . implode(", ", $steps) . "'");
		}
		return new self(["type" => "step_slider", "text" => $label, "steps" => $steps, "default" => $default_index]);
	}

	/**
	 * Creates a toggle checkbox initialized with a $default value. Returns a
	 * boolean value representing whether the checkbox is checked/enabled.
	 *
	 * @param string $label
	 * @param bool $default
	 * @return self
	 */
	public static function toggle(string $label, bool $default = false) : self{
		return new self(["type" => "toggle", "text" => $label, "default" => $default]);
	}

	/**
	 * A control is represented as a pair of client-side $data and server-side $tags.
	 * Tags evaluate how return values from controls must be represented.
	 * @see ResponseProcessor::process() for more details.
	 *
	 * @param array{type: string, text: string}&array<string, mixed> $data
	 * @param array<string, mixed> $tags
	 */
	private function __construct(
		readonly public array $data,
		readonly public array $tags = []
	){}
}