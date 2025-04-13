<?php

namespace App\Http\Controllers\API;

use OpenApi\Annotations as OA;

use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\BookBorrowed;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Events\BookReturned;

/**
 * @OA\Tag(name="Borrowings", description="API Endpoints for book borrowing operations")
 */
class BorrowingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/borrowings",
     *     tags={"Borrowings"},
     *     summary="Borrow a book",
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="book_id",
     *         in="query",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book borrowed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="borrowing", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Book not found"),
     *     @OA\Response(response=403, description="Unauthorized to borrow this book")
     * )
     */
    public function borrow(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
        ],[
            'book_id.exists' => 'Book not found.',
        ]);

        if ($validator->fails()) {
            $error_messages = $validator->errors()->all();
            return response()->json(["status" => "failed", "error" => true, "message" => $error_messages],422);
        }    

        $book = Book::find($request->book_id);
        if ($book->status === 'borrowed') {
            return response()->json(["status" => "failed", "error" => true, "message" => ["Book already borrowed."]],422);
        }

        $borrowing = Borrowing::create([
            'user_id' => Auth::id(),
            'book_id' => $book->id,
            'borrowed_at' => now(),
        ]);

        $book->update(['status' => 'borrowed']);

        event(new BookBorrowed($borrowing));

        return response()->json(["status" => "success","error" => false,'message' => 'Book borrowed successfully',"data" => $borrowing]);
    }

    /**
     * @OA\Post(
     *     path="/api/borrowing-return/{id}",
     *     tags={"Borrowings"},
     *     summary="Return a borrowed book",
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Borrowing ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="borrowing", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Borrowing record not found"),
     *     @OA\Response(response=403, description="Unauthorized to return this book")
     * )
     */
    public function returnBook($id)
    {
        $borrowing = Borrowing::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$borrowing) {
            return response()->json(["status" => "failed", "error" => true, "message" => ["Borrowing not found."]],404);
        }

        if ($borrowing->returned_at) {
            return response()->json(["status" => "failed", "error" => true, "message" => ["Book already returned."]],422);
        }

        $borrowing->update(['returned_at' => now()]);
        $borrowing->book->update(['status' => 'available']);

        event(new BookReturned($borrowing));

        return response()->json(['message' => 'Book returned successfully', 'data' => $borrowing]);
    }
}
