<?php

namespace Hrmpfz\PayBySquare\Lzma;

/**
 * Simplified literal-only LZMA range encoder.
 *
 * This encoder only writes literal symbols (no LZ77 back-references).
 * This makes the implementation extremely simple (~60 lines of code)
 * and robust, while generating valid LZMA streams that are only
 * slightly larger than standard compressed streams.
 */
class RangeEncoder
{
    private int $low = 0;
    private int $rrange = 0xFFFFFFFF;
    private int $cacheSize = 1;
    private int $cache = 0;
    private array $buf = [];

    /**
     * Encode a single bit using a probability model index.
     */
    public function encodeBit(array &$probs, int $index, int $symbol): void
    {
        $prob = $probs[$index];
        $newBound = ($this->rrange >> 11) * $prob;

        if ($symbol === 0) {
            $this->rrange = $newBound;
            // Update probability towards 0
            $probs[$index] = $prob + ((2048 - $prob) >> 5);
        } else {
            $this->low += $newBound;
            $this->rrange -= $newBound;
            // Update probability towards 1
            $probs[$index] = $prob - ($prob >> 5);
        }

        if ($this->rrange < 0x1000000) {
            $this->rrange <<= 8;
            $this->shiftLow();
        }
    }

    /**
     * Shift low value out to the buffer.
     */
    private function shiftLow(): void
    {
        $lowHi = $this->low >> 32;
        if ($lowHi !== 0 || $this->low < 0xFF000000) {
            $temp = $this->cache;
            do {
                $this->buf[] = ($temp + $lowHi) & 0xFF;
                $temp = 255;
            } while (--$this->cacheSize !== 0);
            $this->cache = ($this->low >> 24) & 0xFF;
        }
        $this->cacheSize++;
        $this->low = ($this->low & 0xFFFFFF) << 8;
    }

    /**
     * Finish encoding and flush the remaining range coder state.
     */
    public function finish(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->shiftLow();
        }
    }

    /**
     * Get the compressed byte array.
     *
     * @return array<int>
     */
    public function getBuffer(): array
    {
        return $this->buf;
    }

    /**
     * Static helper to compress data using literal-only LZMA.
     *
     * @param string $data Uncompressed binary string.
     * @return string Compressed raw LZMA body.
     */
    public static function compress(string $data): string
    {
        $rc = new self();

        // Initialize probability models
        // isMatch contains 192 elements
        $isMatch = array_fill(0, 192, 1024);

        // literalCoders contains 8 sub-coders, each having 768 elements
        $literalCoders = [];
        for ($i = 0; $i < 8; $i++) {
            $literalCoders[$i] = array_fill(0, 768, 1024);
        }

        $length = strlen($data);
        $previousByte = 0;

        for ($i = 0; $i < $length; $i++) {
            $curByte = ord($data[$i]);
            $posState = $i & 3; // posStateMask = 3 (pb = 2)

            // Encode isMatch[posState] as 0 (indicating a literal symbol)
            $rc->encodeBit($isMatch, $posState, 0);

            // Determine subcoder index using previous byte context (lc = 3, lp = 0)
            $subCoderIndex = $previousByte >> 5;
            $subCoder = &$literalCoders[$subCoderIndex];

            $context = 1;
            for ($bitIndex = 7; $bitIndex >= 0; $bitIndex--) {
                $bit = ($curByte >> $bitIndex) & 1;
                $rc->encodeBit($subCoder, $context, $bit);
                $context = ($context << 1) | $bit;
            }

            $previousByte = $curByte;
        }

        $rc->finish();

        return pack('C*', ...$rc->getBuffer());
    }
}
