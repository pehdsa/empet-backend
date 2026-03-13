<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class PaginatorServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /**
         * Exemplo parametros da URL
         * ?page=1&per_page=15&sort=column:asc
         */
        Builder::macro('paginateFromRequest', function (string $orderBy = 'created_at', string $initialDirection = 'desc') {
            $request = request();

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $isValidColumnName = fn (string $name): bool => preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) === 1;

            $sort = $orderBy;
            $direction = $initialDirection;

            if ($request->exists('sort')) {
                [$sort, $direction] = array_pad(explode(':', $request->input('sort', $orderBy)), 2, $initialDirection);
                $direction = strtolower($direction);

                if (! in_array($direction, ['asc', 'desc'])) {
                    $direction = $initialDirection;
                }

                if (! $isValidColumnName($sort)) {
                    $sort = $orderBy;
                }
            }

            $this->orderBy($sort, $direction);

            return $this->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        });
    }
}
