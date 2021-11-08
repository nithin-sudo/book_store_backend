<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Book;
use Exception;
use Validator;
use JWTAuth;
use Auth;

class BookController extends Controller
{
    public function addNewBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|between:2,50',
            'description' => 'required|string|between:3,1000',
            'author' => 'required|string|between:3,100',
            'logo' => 'required|string|between:3,1000',
            'price' => 'required|numeric',
            'quantity'=>'required|integer|min:1'
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try 
		{
            $currentUser = JWTAuth::parseToken()->authenticate();
            if($currentUser)
            {
                $adminId = User::select('id')->where([['role','=','admin'],['id','=',$currentUser->id]])->get();

                if(count($adminId)==0)
                {
                    return response()->json([ 'message' => 'Unauthorized'], 404);
                }
                $book = Book::where('title',$request->title)->first();
                if($book)
                {
                    return response()->json(['message' => 'You are trying to add Existing Book'], 401);
                }
                $book = new Book;
                $book->title = $request->input('title');
                $book->description = $request->input('description');
                $book->author = $request->input('author');
                $book->logo = $request->input('logo');
                $book->price = $request->input('price');
                $book->quantity = $request->input('quantity');
                $book->user_id = $currentUser->id;
                $book->save();
            }
        } 
		catch (Exception $e) 
		{
            Log::error('Invalid User');
            return response()->json([ 'message' => 'Invalid authorization token'], 404);
        }

        Log::info('book created',['admin_id'=>$book->admin_id]);
        return response()->json(['message' => 'Book created successfully'],201);
    }

    
    public function addExistingBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'=>'required',
            'quantity'=>'required|integer|min:1'
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try 
		{
            $currentUser = JWTAuth::parseToken()->authenticate();
            $adminId = User::select('id')->where([['role','=','admin'],['id','=',$currentUser->id]])->get();
            if(count($adminId)==0)
            {
                return response()->json([ 'message' => 'Unauthorized'], 404);
            }
            $book = Book::find($request->id);
            if(!$book)
            {
                return response()->json(['message' => 'Could not found Book with that id'], 404);
            }
            $book->quantity += $request->quantity;
            $book->save();
            return response()->json([ 'message' => 'Book Quantity updated Successfully'], 201);
        }       
        catch (Exception $e) 
		{
            Log::error('Invalid User');
            return response()->json([ 'message' => 'Invalid authorization token'], 404);
        }
    }

    public function updateBookById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'title' => 'string|between:2,50',
            'description' => 'string|between:3,1000',
            'author' => 'string|between:3,100',
            'logo' => 'string|between:3,1000',
            'price' => 'numeric',
            'quantity'=>'integer|min:1'
        ]);
        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try
        {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $admin = User::select('id')->where([
                ['role','=','admin'],
                ['id','=',$currentUser->id]
            ])->get();

            if(count($admin) == 0)
            {
                return response()->json(['message' => 'Un authorized' ], 401);
            }
            $id = $request->input('id');
            $book=Book::find($request->id);
            if(!$book)
            {
                return response()->json([ 'message' => 'Book not Found'], 404);
            }
            $book->fill($request->all());
            if($book->save())
            {
                return response()->json(['message' => 'Book updated Sucessfully' ], 201);
            } 
                 
        }
        catch(Exception $e)
        {
            return response()->json(['message' => 'Invalid authorization token' ], 404);
        }
    }


    public function deleteBookById(Request $request)
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
            $currentUser = JWTAuth::parseToken()->authenticate();
            $admin = User::select('id')->where([
                ['role','=','admin'],
                ['id','=',$currentUser->id]
            ])->get();

            if(count($admin) == 0)
            {
                return response()->json(['message' => 'Un authorized' ], 401);
            }
            $id = $request->input('id');
            $book=Book::find($request->id);
            if(!$book)
            {
                return response()->json([ 'message' => 'Book not Found'], 404);
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

    

    public function getAllBooks()
    {           
        $books = Book::select('id','title','description','author','logo','price','quantity')->get();
        if ($books=='[]')
        {
            return response()->json(['message' => 'Books not found'], 404);
        }
        return response()->json([
            'books' => $books,
            'message' => 'Fetched Books Successfully'
        ], 201);
    }
}
