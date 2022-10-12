<?php 

namespace One\Caller;

class ServiceProvider
{
	/**
	 * @var array
	 */
	private static $bindings = [];

	/**
	 * All of the container bindings that should be registered
	 * @return array
	 */
	public static function setBindings(array $bindings): void
	{
		self::$bindings = $bindings;
	}

	public static function getBindings(): array
	{
		return self::$bindings;
	}
}