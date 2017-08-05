<?php

namespace Helper;

use Symfony\Component\Console\Style\StyleInterface;

/**
 * Null implementation for Symfony Style interface.
 */
final class NullStyle implements StyleInterface
{
    public function title($message)
    {
    }

    public function section($message)
    {
    }

    public function listing(array $elements)
    {
    }

    public function text($message)
    {
    }

    public function success($message)
    {
    }

    public function error($message)
    {
    }

    public function warning($message)
    {
    }

    public function note($message)
    {
    }

    public function caution($message)
    {
    }

    public function table(array $headers, array $rows)
    {
    }

    public function ask($question, $default = null, $validator = null)
    {
    }

    public function askHidden($question, $validator = null)
    {
    }

    public function confirm($question, $default = true)
    {
    }

    public function choice($question, array $choices, $default = null)
    {
    }

    public function newLine($count = 1)
    {
    }

    public function progressStart($max = 0)
    {
    }

    public function progressAdvance($step = 1)
    {
    }

    public function progressFinish()
    {
    }
}
