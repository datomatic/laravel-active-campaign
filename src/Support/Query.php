<?php

namespace Datomatic\ActiveCampaign\Support;

use Datomatic\ActiveCampaign\Enums\FilterOperator;
use Stringable;

/**
 * Builds the query strings the API expects, so callers do not have to hand-write
 * `filters[created_after]=...&orders[cdate]=DESC`.
 *
 * Accepted anywhere a resource takes a query, and still convertible to a plain string.
 */
class Query implements Stringable
{
    /** @var array<string, mixed> */
    protected array $params = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * A filter, optionally with one of the operators the API supports.
     * Without an operator the API compares for equality.
     */
    public function filter(string $field, mixed $value, ?FilterOperator $operator = null): self
    {
        $value = $this->normalize($value);

        if (is_null($operator)) {
            $this->params['filters'][$field] = $value;
        } else {
            $this->params['filters'][$field][$operator->value] = $value;
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $filters  field => value, all compared for equality
     */
    public function filters(array $filters): self
    {
        foreach ($filters as $field => $value) {
            $this->filter($field, $value);
        }

        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->params['orders'][$field] = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return $this;
    }

    public function orderByDesc(string $field): self
    {
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Side-load related resources, e.g. include('contactTags', 'contactLists').
     */
    public function include(string ...$relations): self
    {
        $current = isset($this->params['include']) ? explode(',', strval($this->params['include'])) : [];

        $this->params['include'] = implode(',', array_values(array_unique([...$current, ...$relations])));

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->params['limit'] = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->params['offset'] = $offset;

        return $this;
    }

    /**
     * A top-level parameter the API defines outside the filters syntax,
     * such as contacts' `email`, `search`, `listid`, `segmentid` or `id_greater`.
     */
    public function where(string $key, mixed $value): self
    {
        $this->params[$key] = $this->normalize($value);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->params;
    }

    public function __toString(): string
    {
        return http_build_query($this->params);
    }

    protected function normalize(mixed $value): mixed
    {
        return match (true) {
            is_bool($value) => $value ? 1 : 0,
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
            is_array($value) => implode(',', $value),
            default => $value,
        };
    }
}
