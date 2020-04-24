<?php

namespace Transprime\Arrayed;

use ArrayIterator;
use Transprime\Arrayed\Types\Undefined;
use Transprime\Arrayed\Interfaces\ArrayedInterface;

class Arrayed implements ArrayedInterface
{
    private array $values;

    private $lastResult;

    public function __construct(...$values)
    {
        if (func_num_args() === 1 && is_array($values[0])) {
            $this->values = $values[0];
        } else {
            $this->values = $values;
        }

        $this->setLastResult(new Undefined());
    }

    public function __invoke(callable $callable = null)
    {
        return $this->result($callable);
    }

    public function map($callback): ArrayedInterface
    {
        return $this->setLastResult(array_map($callback, $this->getWorkableItem()));
    }

    public function filter($callback = null, int $flag = 0): ArrayedInterface
    {
        if ($callback) {
            return $this->setLastResult(array_filter($this->getWorkableItem(), $callback, $flag));
        }

        return $this->setLastResult(array_filter($this->getWorkableItem()));
    }

    public function reduce($function, $initial = null): ArrayedInterface
    {
        return $this->setLastResult(array_reduce($this->getWorkableItem(), $function, $initial));
    }

    public function merge(array $array2 = null, ...$_): ArrayedInterface
    {
        return $this->setLastResult(array_merge($this->getWorkableItem(), $array2, ...$_));
    }

    public function mergeRecursive(...$_): ArrayedInterface
    {
        return $this->setLastResult(array_merge_recursive($this->getWorkableItem(), ...$_));
    }

    public function flip(): ArrayedInterface
    {
        return $this->setLastResult(array_flip($this->getWorkableItem()));
    }

    public function intersect(array $array2, ...$_): ArrayedInterface
    {
        return $this->setLastResult(array_intersect($this->getWorkableItem(), $array2, ...$_));
    }

    public function values(): ArrayedInterface
    {
        return $this->setLastResult(array_values($this->getWorkableItem()));
    }

    public function keys($overwrite = true): ArrayedInterface
    {
        $keys = array_keys($this->getWorkableItem());

        if (!$overwrite) {
            return $this->makeArrayed($keys);
        }

        return $this->setLastResult($keys);
    }

    public function offsetGet($offset)
    {
        return $this->makeArrayed($this->getWorkableItem()[$offset]);
    }

    public function offsetSet($offset, $value): ArrayedInterface
    {
        return $this->merge([$offset => $value]);
    }

    public function offsetUnset($offset): ArrayedInterface
    {
        $item = $this->getWorkableItem();

        unset($item[$offset]);

        return $this->setLastResult($item);
    }

    //Scalar returns

    public function sum(): int
    {
        return array_sum($this->getWorkableItem());
    }

    public function contains($needle, bool $strict = false): bool
    {
        return in_array($needle, $this->getWorkableItem(), $strict);
    }

    public function isArray(): bool
    {
        return is_array($this->getWorkableItem());
    }

    public function keyExists($key): bool
    {
        return array_key_exists($key, $this->getWorkableItem());
    }

    public function offsetExists($offset): bool
    {
        return $this->keyExists($offset);
    }

    public function empty(): bool
    {
        return empty($this->getWorkableItem());
    }

    public function count(): int
    {
        return count($this->getWorkableItem());
    }

    //Getters to end chained calls

    public function getIterator()
    {
        return new ArrayIterator($this->getWorkableItem());
    }

    public function pipe(callable $action, ...$parameters)
    {
        return $this->setLastResult(
            piper($this->getWorkableItem())->to($action, ...$parameters)()
        );
    }

    private function setLastResult($value)
    {
        $this->lastResult = $value;

        return $this;
    }

    public function result(callable $callable = null)
    {
        return $callable ? $callable($this->lastResult) : $this->getWorkableItem();
    }

    private function getWorkableItem(bool $asArray = false)
    {
        if ($this->lastResult instanceof Undefined) {
            return $this->values;
        }

        return ($asArray && !is_array($this->lastResult)) ? [$this->lastResult] : $this->lastResult;
    }

    private static function makeArrayed($data)
    {
        return is_array($data) ? new static($data) : $data;
    }
}
