<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Book;
use App\Models\cart;
use Exception;
use Validator;
use JWTAuth;
use Auth;

class CartController extends Controller
{
    public function addBookToCartByBookId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|integer|min:1',
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $currentUser = JWTAuth::parseToken()->authenticate();
        if ($currentUser)
        {
            $book_id = $request->input('book_id');
            $book_quantity = Book::select('quantity')->where([
                ['id','=',$book_id]
            ])->get();

            if(!$book_quantity)
            {
                return response()->json([ 'message' => 'Book not Found'], 404);
            }
            $book = Book::find($book_id);
            if ($book->quantity == 0)
            {
                return response()->json([ 'message' => 'OUT OF STOCK'], 404);
            }
            $book_cart = Cart::select('id')->where([
                ['book_status','=','cart'],
                ['book_id','=',$book_id],
                ['user_id','=',$currentUser->id]
            ])->get();

            if(!$book_cart)
            {
                return response()->json([ 'message' => 'Book already added'], 404);
            }

            $cart = new Cart;
            $cart->book_id = $request->get('book_id');

            if($currentUser->carts()->save($cart))
            {
                return response()->json(['message' => 'Book added to Cart Sucessfully'], 201);
            }
            return response()->json(['message' => 'Book cannot be added to Cart'], 405);
        }
        return response()->json(['message' => 'Invalid authorization token'], 404);
    }

    public function deleteBookByCartId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try
        {
            $id = $request->input('id');
            $currentUser = JWTAuth::parseToken()->authenticate();
            $book = $currentUser->carts()->find($id);
            if(!$book)
            {
                Log::error('Book Not Found',['id'=>$request->id]);
                return response()->json(['message' => 'Book not Found'], 404);
            }

            if($book->delete())
            {
                Log::info('book deleted',['user_id'=>$currentUser,'book_id'=>$request->id]);
                return response()->json(['message' => 'Book deleted Sucessfully'], 201);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['message' => 'Invalid authorization token' ], 404);
        }
    } 


    public function getAllBooksByUserId()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        if ($currentUser) 
        {
            $books = Cart::leftJoin('books', 'carts.book_id', '=', 'books.id')
            ->select('books.id','books.title','books.author','books.description','books.price','carts.book_quantity')
            ->where('carts.user_id','=',$currentUser->id)
            ->get();
                
            if ($books == '[]')
            {
                return response()->json(['message' => 'Books not found'], 404);
            }
            return response()->json([
                'notes' => $books,
                'message' => 'Fetched Books Successfully'
            ], 201);
        }
        return response()->json([ 'message' => 'Invalid Authorization token'],403);
    }
}
