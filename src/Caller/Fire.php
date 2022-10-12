<?php 

namespace One\Caller;

class Fire
{
	public static function callingFunctionsThatHaveAtsign(string $fn, array $args=[])
	{
		list($class, $method) = explode('@', $fn);

		list($method, $params) = self::splitFunctionAndArgs(fn: $method);
		$args = array_merge($args, $params);

		return self::callClass(class: $class, method: $method, args: $args);
	}

	public static function callingFunctionsThatHaveNotAtsign(string $fn, array $args=[])
	{
		list($fn, $params) = self::splitFunctionAndArgs(fn: $fn);
		$args = array_merge($args, $params);

		if ( class_exists($fn) ) {
            $fire = self::callClassWithoutMethod(class: $fn, args: $args);
        } else {
            $fire = call_user_func($fn, ...$args);
        }

        return $fire;
	}

	private static function bindClass(string $class): string
	{
		$bindings = ServiceProvider::getBindings();
		return $bindings[$class] ?? $class;
	}

	private static function splitFunctionAndArgs(string $fn)
	{
		if (strpos($fn, ':') !== false) {
			list($fn, $args) = explode(':', $fn);
			$args = explode(',', $args);
		} else {
			$args = [];
		}

		return [$fn, $args];
	}

	private static function callClassWithoutMethod(string $class, array $args=[])
	{
		return method_exists($class, "__construct")
				? self::callClass(class: $class, method: "__construct", args: $args)
				: new $class;
	}

	private static function callClass(string $class, string $method, array $args)
	{
		$class = self::bindClass(class: $class);
		$args = self::injectDependency(class: $class, method: $method, args: $args);

		if ($method == "__construct") {
			$callable = [new \ReflectionClass($class), 'newInstanceArgs'];
			$args = [$args];
		} else {
			$callable = [new $class, $method];
		}

		return call_user_func_array($callable, $args);
	}

	private static function injectDependency(string $class, string $method, array $args): array
	{
		$classOfFirstArg = self::getClassName($args[0]??[]);

		$params = self::extractAllParamsOfClass(class: $class, method: $method);
		foreach ($params as $param) {
			$classOfParam = self::getClassName($param);
			if ($classOfFirstArg == $classOfParam) {
				break;
			}
            $dependency = self::callClassWithoutMethod(class: $classOfParam);
            array_unshift($args, $dependency);
		}

		return $args;
	}

	private static function extractAllParamsOfClass(string $class, string $method): array
	{
		$ReflectionMethod = new \ReflectionMethod($class, $method);
		return $ReflectionMethod->getParameters();
	}

	private static function getClassName($arg): ?string
	{
		if ($arg instanceof \ReflectionParameter) {
			$className = $arg->getClass()->name ?? null;
		} else {
			$className = is_object($arg) ? get_class($arg) : null;
		}

		return $className;
	}
}