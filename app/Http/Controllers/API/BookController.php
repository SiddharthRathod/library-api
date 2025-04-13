<?php

namespace App\Http\Controllers\API;

use OpenApi\Annotations as OA;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(name="Books", description="API Endpoints for managing books")
 */
class BookController extends Controller
{
    
    /**
     * @OA\Get(
     *     path="/api/books",
     *     tags={"Books"},
     *     summary="List all books with filters and pagination",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in title, author, ISBN, or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by book status",
     *         @OA\Schema(type="string", enum={"available", "borrowed"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         @OA\Schema(type="string", enum={"title", "author", "isbn", "published_at", "status", "created_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort direction",
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of books",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $search = request('search');
        $status = (request('status')) ? request('status') : 'available';
        $sortBy = request('sort_by', 'created_at');
        $sortOrder = request('sort_order', 'desc');
        $page = request('page', 1);
        $cacheKey = "books_page_{$page}_search_{$search}_status{$status}_sort_{$sortBy}_order_{$sortOrder}";
        $cacheMinutes = 60;
        
        $books = Cache::remember($cacheKey, $cacheMinutes, function () use ($search, $status, $sortBy, $sortOrder) {
            
            $query = Book::query();
        
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $query->where('status', $status);
            
            $allowedSortFields = ['title', 'author', 'isbn', 'published_at', 'status', 'created_at'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
        
            return $query->orderBy($sortBy, $sortOrder)->paginate(50);
        });

        return response()->json($books);
    }

    /**
     * @OA\Get(
     *     path="/api/books/{id}",
     *     tags={"Books"},
     *     summary="Get a specific book by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="error", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['status' => 'failed','error' => true,'message' => ['Book not found.']], 404);
        }

        return response()->json(["status" => "success","error" => false,'message' => 'Book found successfully', 'data' => $book], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/books",
     *     tags={"Books"},
     *     summary="Create a new book",
     *     security={"bearerAuth": {}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","author","isbn"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="author", type="string"),
     *             @OA\Property(property="isbn", type="string"),
     *             @OA\Property(property="published_at", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"available", "borrowed"}),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="error", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'author' => 'required|string',
            'isbn' => 'required|string|unique:books,isbn',
            'published_at' => 'nullable|date',
            'status' => 'in:available,borrowed',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $error_messages = $validator->errors()->all();
            return response()->json(["status" => "failed", "error" => true, "message" => $error_messages],422);
        }    
        
        $data = $request->only('title', 'author', 'isbn', 'published_at', 'status', 'description');

        $book = Book::create($data);
        Cache::flush(); 
        return response()->json(["status" => "success","error" => false,'message' => 'Book created successfully', 'data' => $book], 201);

    }

    /**
     * @OA\Put(
     *     path="/api/books/{id}",
     *     tags={"Books"},
     *     summary="Update a specific book",
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="author", type="string"),
     *             @OA\Property(property="isbn", type="string"),
     *             @OA\Property(property="published_at", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"available", "borrowed"}),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="error", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Book not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['status' => 'failed','error' => true,'message' => ['Book not found.']], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string',
            'author' => 'sometimes|required|string',
            'isbn' => 'sometimes|required|string|unique:books,isbn,' . $book->id,
            'published_at' => 'nullable|date',
            'status' => 'in:available,borrowed',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $error_messages = $validator->errors()->all();
            return response()->json(["status" => "failed", "error" => true, "message" => $error_messages],422);
        }    
        
        $data = $request->only('title', 'author', 'isbn', 'published_at', 'status', 'description');
        $book->update($data);
        Cache::flush();

        return response()->json(["status" => "success","error" => false,'message' => 'Book updated successfully', 'data' => $book], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/books/{id}",
     *     tags={"Books"},
     *     summary="Delete a specific book",
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="error", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Book not found"),
     *     @OA\Response(response=422, description="Book cannot be deleted")
     * )
     */
    public function destroy($id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(['status' => 'failed','error' => true,'message' => ['Book not found.']], 404);
        }
        
        if ($book->status == 'borrowed') {
            return response()->json(['status' => 'failed', 'error' => true, 'message' => ['Can not delete a borrowed book.']], 422);
        }

        $book->delete();
        Cache::flush();

        return response()->json(["status" => "success","error" => false,'message' => 'Book deleted successfully'], 201);
    }
}

