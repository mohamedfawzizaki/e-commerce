<?php

namespace App\Services\Products;

use App\Models\Cart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Products\CartRepository;

class CartService
{
    /**
     * Constructor to inject the CartRepository dependency.
     *
     * @param \App\Repositories\Repos\Products\CartRepository $cartRepository
     */
    public function __construct(private CartRepository $cartRepository) {}

    public function getAllCarts(
        ?int $perPage = null,
        array $columns = ['*'],
        ?string $pageName = null,
        ?int $page = null,
        bool $withTrashed = false,
        bool $onlyTrashed = false,
        array $conditions = []
    ): Collection|LengthAwarePaginator {
        return $this->cartRepository->getAllUsingRepositoryBaseTrait(
            $perPage,
            $columns,
            $pageName,
            $page,
            $withTrashed,
            $onlyTrashed,
            $conditions
        );
    }

    public function getCartById(string $id, array $columns = ['*']): ?object
    {
        return $this->cartRepository->getByIdUsingRepositoryBaseTrait($id, $columns);
    }

    public function searchBy(string $field, mixed $value, array $columns = ['*']): ?object
    {
        return $this->cartRepository->searchByUsingRepositoryBaseTrait($field, $value, $columns);
    }

    public function create(array $data): ?object
    {
        return $this->cartRepository->createUsingRepositoryBaseTrait($data);
    }

    public function update(string $id, array $data, array $columns = ['*']): ?object
    {
        return $this->cartRepository->updateUsingRepositoryBaseTrait($id, $data, $columns);
    }

    public function delete(string $id, bool $force = false)
    {
        return $this->cartRepository->deleteUsingRepositoryBaseTrait($id, $force);
    }

    public function softDeleted(string $id)
    {
        return $this->cartRepository->softDeletedUsingRepositoryBaseTrait($id);
    }

    public function restore(string $id, array $columns = ['*'])
    {
        return $this->cartRepository->restoreUsingRepositoryBaseTrait($id, $columns);
    }

    public function clearCart(?string $userId = null, ?string $sessionId = null): void
    {
        Cart::when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->delete();
    }
}