<?php

declare(strict_types=1);

namespace PhpMvc;

use Psr\Container\ContainerInterface;

/**
 * A PSR-11 container that also supports registering entries.
 *
 * The consumer's bootstrap is responsible for providing an implementation
 * before passing the container to any framework class.
 */
interface MutableContainerInterface extends ContainerInterface
{
    public function set(string $id, mixed $value): void;
}
