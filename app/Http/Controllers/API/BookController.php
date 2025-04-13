<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    
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

    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['status' => 'failed','error' => true,'message' => ['Book not found.']], 404);
        }

        return response()->json(["status" => "success","error" => false,'message' => 'Book found successfully', 'data' => $book], 201);
    }

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

