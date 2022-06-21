<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/project-builder".
 *
 * Copyright (C) 2022 Elias Häußler <e.haeussler@familie-redlich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace CPSIT\ProjectBuilder\IO;

/**
 * Emoji.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class Emoji
{
    public const HOURGLASS_FLOWING_SAND = "\u{23F3}";
    public const PARTY_POPPER = "\u{1F389}";
    public const PROHIBITED = "\u{1F6AB}";
    public const ROTATING_LIGHT = "\u{1F6A8}";
    public const SPARKLES = "\u{2728}";
    public const WHITE_HEAVY_CHECK_MARK = "\u{2705}";
    public const WOOZY_FACE = "\u{1F974}";
}
