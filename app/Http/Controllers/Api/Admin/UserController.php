<?php

namespace App\Http\Controllers\Api\Admin;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Rules\StrongPassword;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class UserController extends Controller
{
    /**
     * Constructor to inject the UserService dependency.
     *
     * @param UserService $userService The service responsible for user-related operations.
     */
    public function __construct(protected UserService $userService) {}

    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            // Extract validated input values with default fallbacks.
            $validated = $request->validated();

            $paginate = $validated['paginate'] ?? false;
            $withTrashed = $validated['with_trashed'] ?? false;
            $onlyTrashed = $validated['only_trashed'] ?? false;
            $conditions = $validated['conditions'] ?? [];
            $columns = $validated['columns'] ?? ['*'];

            $users = $paginate
                ? $this->userService->getAllUsers(
                    perPage: $validated['per_page'] ?? 15, // Default to 15 if not specified.
                    columns: $columns,
                    pageName: $validated['pageName'] ?? 'page',
                    page: $validated['page'] ?? 1,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->userService->getAllUsers(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($users, 'Users retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $user = $this->userService->getUserById($id, $columns);

            return ApiResponse::success($user, 'User retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving user: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function searchBy(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'name' => 'required_without:email|string|exists:users,name',
                'email' => 'required_without:name|email|exists:users,email',
                'columns' => 'sometimes|array', // Optional columns parameter.
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User retrieval validation failed.", [
                    'errors' => $validator->errors(),
                    'input' => $request->all(), // Log the provided input for debugging.
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            // Extract validated data.
            $validated = $validator->validated();
            $searchBy = ($validated['email'] ?? null) ? 'email' : 'name';
            $columns = $validated['columns'] ?? ['*'];

            $user = $this->userService->searchBy($searchBy, $validated[$searchBy], $columns);

            // Return success response.
            return ApiResponse::success($user, 'User retrieved successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error retrieving user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            // Extract validated data.
            $validated = $request->validated();

            $user = $this->userService->create($validated);

            // assign address to a user
            $cityID = $request->validated()['city_id'] ?? null;
            $cityName = $request->validated()['city_name'] ?? null;

            $result = $cityID ? $this->userService->assignAddressByCityID($cityID, $user->id) :
                $this->userService->assignAddressByCityName($cityName, $user->id);

            $user = $this->userService->getUserById($user->id);
            // Return success response.
            return ApiResponse::success($user, 'User created successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error creating user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        var_dump($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string|unique:users,name|max:255',
                'email' => 'email|unique:users,email',
                'password' => ['sometimes', 'confirmed',  new StrongPassword],

                'columns'  => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }
            // Extract validated data.
            $validatedData = $request->except(['columns']);
            $columns = empty($request->only(['columns'])) ? ['*'] : $request->only(['columns']);

            $user = $this->userService->update($id, $validatedData, $columns);

            // Return success response.
            return ApiResponse::success($user, 'User updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function updateBulk(Request $request): JsonResponse
    {
        try {
            // Validate input parameters.
            $validator = Validator::make($request->all(), [
                'password'    => ['sometimes', 'confirmed', new StrongPassword],
                'created_at'  => 'sometimes|date',
                'updated_at'  => 'sometimes|date',
                'deleted_at'  => 'sometimes|date',

                'conditions'  => 'sometimes|array',
                'columns'     => 'sometimes|array',
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("Users updating validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            };
            $validated = $validator->validated();

            $conditions = $validator->validated()['conditions'] ?? [];
            $columns = $validator->validated()['columns'] ?? ['*'];

            // Convert created_at & updated_at if provided
            if (!empty($validated['created_at'])) {
                $validated['created_at'] = Carbon::parse($validated['created_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['updated_at'])) {
                $validated['updated_at'] = Carbon::parse($validated['updated_at'])->format('Y-m-d H:i:s');
            }

            if (!empty($validated['deleted_at'])) {
                $validated['deleted_at'] = Carbon::parse($validated['deleted_at'])->format('Y-m-d H:i:s');
            }

            // Filter only valid user fields (excluding 'columns')
            $data = array_filter($validated, function ($key) {
                return !in_array($key, ['columns', 'conditions']); // Exclude 'columns' key and conditions key
            }, ARRAY_FILTER_USE_KEY);

            // Call update function with or without columns
            $users = $this->userService->updateGroup($data, $conditions, $columns);

            // Return success response.
            return ApiResponse::success($users, 'User updated successfully.');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error updating user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            $user = $this->userService->delete($id, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($user, 'User permenantly deleted successfully.') :
                ApiResponse::success($user, 'User soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting user: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function deleteBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $forceDelete = $request->validated()['force'] ?? false;

            $deletedUsers = $this->userService->deleteBulk($conditions, $forceDelete);

            return $forceDelete ?
                ApiResponse::success($deletedUsers, 'Users permenantly deleted successfully.') :
                ApiResponse::success($deletedUsers, 'Users soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting users: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|string|uuid'
            ]);

            // Handle validation failures.
            if ($validator->fails()) {
                Log::warning("User checking validation failed.", [
                    'errors' => $validator->errors(),
                ]);

                return ApiResponse::error(
                    'Invalid request parameters.',
                    422,
                    $validator->errors()
                );
            }

            $id = $validator->validated()['id'];

            $isDeleted = $this->userService->softDeleted($id);

            return $isDeleted ?
                ApiResponse::success($isDeleted, 'User is soft deleted') :
                ApiResponse::success($isDeleted, 'User is not soft deleted');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error checking soft deleted user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $user = $this->userService->restore($id, $columns);

            return ApiResponse::success($user, 'User is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted user: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restoreBulk(ValidateColumnAndConditionRequest $request)
    {
        try {
            $conditions = $request->validated()['conditions'] ?? [];
            $columns = $request->validated()['columns'] ?? ['*'];

            $users = $this->userService->restoreBulk($conditions, $columns);

            return ApiResponse::success($users, 'User is restored');
        } catch (Exception $e) {
            Log::error("Error restoring users: {$e->getMessage()}", ['exception' => $e]);
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}