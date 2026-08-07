<?php

namespace App\Services\Notifications;

use InvalidArgumentException;

/**
 * Validate + chuẩn hoá DSL đối tượng nhận (spec 07 §2). KHÔNG lưu SQL thô: chỉ chấp nhận
 * field/operator trong whitelist; validate tenant scope ở AudienceResolver (server-side).
 * Nhận cả 2 shape: DSL {scope,include,exclude} và shape phẳng của audience_groups.json.
 */
class AudienceRuleValidator
{
    /** Field điều kiện cho include/exclude + shape phẳng. */
    public const FIELDS = [
        'relationship_role', 'relationship_roles', 'relationship_active', 'resident_status',
        'has_app', 'active_device', 'has_email', 'has_phone', 'has_zalo', 'language',
        'vehicle_parking_zone', 'has_published_statement', 'access_card_expires_in_days',
        'household_has_child', 'age',
    ];

    /** Field phạm vi (cây dự án/tòa/tầng/căn). */
    public const SCOPE_FIELDS = [
        'tenant_ids', 'project_ids', 'building_ids', 'building_codes', 'floor_ids',
        'apartment_ids', 'apartment_codes', 'resident_ids', 'user_ids',
    ];

    public const OPERATORS = ['eq', 'in', 'not_in', 'lte', 'gte', 'between', 'exists', 'contains'];

    /**
     * Validate rule; throw InvalidArgumentException nếu sai. Trả về rule chuẩn hoá.
     *
     * @return array{version:int,scope:array<string,mixed>,include:array<int,array<string,mixed>>,exclude:array<int,array<string,mixed>>}
     */
    public function validate(array $rule): array
    {
        $normalized = $this->normalize($rule);

        foreach ($normalized['scope'] as $key => $_) {
            if (! in_array($key, self::SCOPE_FIELDS, true)) {
                throw new InvalidArgumentException("Trường phạm vi không hợp lệ: {$key}.");
            }
        }
        foreach (['include', 'exclude'] as $bucket) {
            foreach ($normalized[$bucket] as $cond) {
                if (! isset($cond['field']) || ! in_array($cond['field'], self::FIELDS, true)) {
                    throw new InvalidArgumentException("Trường điều kiện không hợp lệ: ".($cond['field'] ?? '(trống)').".");
                }
                if (! isset($cond['operator']) || ! in_array($cond['operator'], self::OPERATORS, true)) {
                    throw new InvalidArgumentException("Toán tử không hợp lệ: ".($cond['operator'] ?? '(trống)').".");
                }
            }
        }

        return $normalized;
    }

    public function isValid(array $rule): bool
    {
        try {
            $this->validate($rule);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Chuẩn hoá về {version,scope,include,exclude}. Shape phẳng (audience_groups.json)
     * → mọi key SCOPE vào scope; key FIELD vào include với operator suy từ kiểu value.
     */
    public function normalize(array $rule): array
    {
        // Đã là DSL.
        if (isset($rule['include']) || isset($rule['exclude']) || isset($rule['scope'])) {
            return [
                'version' => (int) ($rule['version'] ?? 1),
                'scope' => (array) ($rule['scope'] ?? []),
                'include' => array_values((array) ($rule['include'] ?? [])),
                'exclude' => array_values((array) ($rule['exclude'] ?? [])),
            ];
        }

        // Shape phẳng.
        $scope = [];
        $include = [];
        foreach ($rule as $key => $value) {
            if (in_array($key, self::SCOPE_FIELDS, true)) {
                $scope[$key] = $value;

                continue;
            }
            $include[] = ['field' => $key, 'operator' => $this->inferOperator($value), 'value' => $value];
        }

        return ['version' => 1, 'scope' => $scope, 'include' => $include, 'exclude' => []];
    }

    private function inferOperator(mixed $value): string
    {
        if (is_array($value)) {
            return isset($value['lte']) || isset($value['gte']) ? 'between' : 'in';
        }

        return 'eq';
    }
}
