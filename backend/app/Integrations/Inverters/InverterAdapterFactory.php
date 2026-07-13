<?php

declare(strict_types=1);

namespace App\Integrations\Inverters;

use App\Integrations\Inverters\Adapters\ElekeeperAdapter;
use App\Integrations\Inverters\Adapters\GoodWeAdapter;
use App\Integrations\Inverters\Adapters\SungrowAdapter;
use App\Integrations\Inverters\Adapters\DeyeAdapter;
use InvalidArgumentException;

class InverterAdapterFactory
{
    private static array $adapters = [
        'elekeeper' => ElekeeperAdapter::class,
        'goodwe' => GoodWeAdapter::class,
        'sungrow' => SungrowAdapter::class,
        'deye' => DeyeAdapter::class,
    ];

    public static function make(string $brand): InverterAdapterInterface
    {
        $adapterClass = self::$adapters[$brand] ?? null;

        if (!$adapterClass) {
            throw new InvalidArgumentException("Adapter não encontrado para marca: {$brand}");
        }

        return new $adapterClass();
    }

    public static function register(string $brand, string $adapterClass): void
    {
        self::$adapters[$brand] = $adapterClass;
    }

    public static function availableBrands(): array
    {
        return array_keys(self::$adapters);
    }
}
