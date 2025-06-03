<?php

namespace Events;

class LogColors
{
    public const COLORS = [
        'Warn'                  => 0xFFA500,
        'Kick'                  => 0x00AAFF,
        'Ban'                   => 0xFF0000,
        'Unban'                 => 0x00FF00,
        'Warnlist'              => 0x8A2BE2,
        'Userinfo'              => 0x8A2BE2,
        'Purge'                 => 0xCCCCCC,
        'Mute'                  => 0xFFFF00,
        'Test'                  => 0xFFFF00,
    ];

    public static function get(string $action): int
    {
        return self::COLORS[$action] ?? 0x5865F2;
    }
}
