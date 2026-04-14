<?php

declare(strict_types=1);

/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright    2000-2026 XOOPS Project (https://xoops.org/)
 * @license      GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author       XOOPS Development Team
 */

namespace Xoops\Helpers\Utility;

/**
 * Fluent pipeline for chaining operations on a value.
 *
 * Passes a value through a series of callable stages,
 * where each stage receives the output of the previous one.
 *
 * Usage:
 *   $result = Pipeline::send($rawInput)
 *       ->through([
 *           fn($v) => trim($v),
 *           fn($v) => strip_tags($v),
 *           fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'),
 *       ])
 *       ->thenReturn();
 *
 *   // Or build incrementally:
 *   $result = Pipeline::send($data)
 *       ->pipe(fn($v) => $v + 1)
 *       ->pipe(fn($v) => $v * 2)
 *       ->thenReturn();
 */
final class Pipeline
{
    private mixed $passable;

    /** @var array<callable> */
    private array $pipes = [];

    private function __construct(mixed $passable)
    {
        $this->passable = $passable;
    }

    /**
     * Create a new pipeline with an initial value.
     */
    public static function send(mixed $passable): self
    {
        return new self($passable);
    }

    /**
     * Set the array of pipeline stages.
     *
     * @param array<callable> $pipes Callable stages
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Add a single stage to the pipeline.
     */
    public function pipe(callable $pipe): self
    {
        $this->pipes[] = $pipe;

        return $this;
    }

    /**
     * Run the pipeline and return the final result.
     */
    public function thenReturn(): mixed
    {
        $result = $this->passable;

        foreach ($this->pipes as $pipe) {
            $result = $pipe($result);
        }

        return $result;
    }

    /**
     * Run the pipeline and pass the result to a final callback.
     */
    public function then(callable $destination): mixed
    {
        return $destination($this->thenReturn());
    }
}
