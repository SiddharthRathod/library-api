<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\BookBorrowed;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BorrowingController extends Controller
{
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
            return response()->json(['message' => 'Book already borrowed.'], 422);
        }

        $borrowing = Borrowing::create([
            'user_id' => Auth::id(),
            'book_id' => $book->id,
            'borrowed_at' => now(),
        ]);

        $book->update(['status' => 'borrowed']);

        event(new BookBorrowed($borrowing));

        return response()->json(['message' => 'Book borrowed successfully', 'data' => $borrowing]);
    }

    public function returnBook($id)
    {
        $borrowing = Borrowing::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$borrowing) {
            return response()->json(['message' => 'Borrowing not found.'], 404);
        }

        if ($borrowing->returned_at) {
            return response()->json(['message' => 'Book already returned.'], 422);
        }

        $borrowing->update(['returned_at' => now()]);
        $borrowing->book->update(['status' => 'available']);

        return response()->json(['message' => 'Book returned successfully', 'data' => $borrowing]);
    }
}
