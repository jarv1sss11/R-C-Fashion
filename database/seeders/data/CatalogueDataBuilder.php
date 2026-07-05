<?php

namespace Database\Seeders\Data;

/**
 * Shared, deterministic product-list generator used by the five per-category
 * data files (MenProducts, WomenProducts, KidsProducts, SportsProducts,
 * AccessoriesProducts). Deliberately not Faker: every list below is
 * hand-authored fashion vocabulary, and the combinations are produced by
 * plain index rotation (not random), so the same seed always yields the
 * same catalogue — important for reproducible recommendation-engine
 * evaluation runs.
 */
class CatalogueDataBuilder
{
    /**
     * @param  string[]  $types  product type names, e.g. "Oxford Shirt"
     * @param  string[]  $materials
     * @param  string[]  $colors
     * @param  string[]  $styles
     * @param  string[]  $seasons
     * @param  string[]  $brands  brand names to rotate through
     * @param  callable  $sizesFor  fn(string $type): ?array
     * @param  callable  $priceFor  fn(string $type, string $material): float
     * @param  callable  $genderFor  fn(string $type, int $index): string
     */
    public static function generate(
        int $count,
        array $types,
        array $materials,
        array $colors,
        array $styles,
        array $seasons,
        array $brands,
        string $ageGroup,
        callable $sizesFor,
        callable $priceFor,
        callable $genderFor,
    ): array {
        $products = [];

        for ($i = 0; $i < $count; $i++) {
            $type = $types[$i % count($types)];
            $material = $materials[($i + intdiv($i, count($types))) % count($materials)];
            $color = $colors[($i * 3 + 1) % count($colors)];
            $style = $styles[($i * 2 + 1) % count($styles)];
            $season = $seasons[$i % count($seasons)];
            $brand = $brands[($i * 5 + 2) % count($brands)];
            $gender = $genderFor($type, $i);

            $suffix = $i >= count($types) ? ' '.self::ordinalVariant(intdiv($i, count($types))) : '';

            // Some type names already bake in a material/colour word (e.g.
            // "Silk Camisole", "Wool Beanie", "Leather Wallet") — drop the
            // rotating material/colour from the name when it would just
            // repeat a word already in the type, to avoid names like
            // "Silver Silver Crossbody Bag" or "Wool Wool Beanie".
            $typeWords = array_map('strtolower', explode(' ', $type));
            $materialWord = strtolower(explode('-', $material)[0]);
            $colorWord = strtolower(explode(' ', $color)[0]);
            $includeMaterial = ! in_array($materialWord, $typeWords, true);
            $includeColor = $colorWord !== $materialWord && ! in_array($colorWord, $typeWords, true);

            $nameParts = array_filter([
                $includeColor ? $color : null,
                $includeMaterial ? $material : null,
                $type,
            ]);
            $name = trim(implode(' ', $nameParts).$suffix);

            $descriptiveType = $includeMaterial ? "{$material} {$type}" : $type;
            $article = in_array(strtolower($style[0]), ['a', 'e', 'i', 'o', 'u'], true) ? 'An' : 'A';
            $description = $includeColor
                ? "{$article} {$style} {$descriptiveType} from {$brand}, finished in {$color} for effortless {$season}-ready wear."
                : "{$article} {$style} {$descriptiveType} from {$brand}, designed for effortless {$season}-ready wear.";

            $products[] = [
                'name' => $name,
                'brand' => $brand,
                'price' => $priceFor($type, $material),
                'color' => $color,
                'sizes' => $sizesFor($type),
                'stock' => self::stockFor($i),
                'material' => $material,
                'season' => $season,
                'style' => $style,
                'gender' => $gender,
                'age_group' => $ageGroup,
                'tags' => array_values(array_unique([strtolower($style), strtolower($season), strtolower($material)])),
                'description' => $description,
                'is_featured' => $i < 4,
                'review_count' => self::reviewCountFor($i),
            ];
        }

        return $products;
    }

    private static function ordinalVariant(int $n): string
    {
        $variants = ['II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];

        return $variants[($n - 1) % count($variants)];
    }

    private static function stockFor(int $i): int
    {
        $cycle = [18, 0, 32, 9, 45, 12, 6, 24, 3, 60];

        return $cycle[$i % count($cycle)];
    }

    private static function reviewCountFor(int $i): int
    {
        $cycle = [0, 3, 8, 12, 1, 15, 5, 0, 9, 2, 11, 4];

        return $cycle[$i % count($cycle)];
    }
}
