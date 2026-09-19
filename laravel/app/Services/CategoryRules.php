<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The ruleset behind each category — fps cap, gravity, speeds, whether autobhop
 * or the hook are allowed. The timer stores all of it and the panel has never
 * shown any of it, so a player cannot tell what they are actually running.
 */
class CategoryRules
{
    /**
     * @return Collection<int, Category> keyed by category id
     */
    public function all(): Collection
    {
        return Cache::remember(
            'category_rules',
            now()->addMinutes(10),
            fn () => Category::all()->keyBy('id')
        );
    }

    public function find(int|string|null $categoryId): ?Category
    {
        return $categoryId === null ? null : $this->all()->get((int) $categoryId);
    }

    /**
     * The handful of rules worth putting on a chip next to the category name.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function chips(int|string|null $categoryId): array
    {
        $category = $this->find($categoryId);

        if ($category === null) {
            return [];
        }

        $chips = [];

        foreach ([
            'fps' => 'fps',
            'gravity' => 'gravity',
            'speed' => 'air speed',
            'ground_speed' => 'ground',
            'start_speed' => 'start',
        ] as $column => $label) {
            if ($category->{$column} !== null) {
                $chips[] = ['label' => $label, 'value' => (string) $category->{$column}];
            }
        }

        foreach ([
            'auto_bhop' => 'autobhop',
            'hook' => 'hook',
            'speedrun' => 'speedrun',
        ] as $column => $label) {
            if ((bool) $category->{$column}) {
                $chips[] = ['label' => $label, 'value' => 'on'];
            }
        }

        return $chips;
    }
}
