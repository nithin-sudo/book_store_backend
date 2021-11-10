<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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

                $imageName = time().'.'.$request->image->extension();  
                $path = Storage::disk('s3')->put('images', $request->image);
                $pathurl = Storage::disk('s3')->url($path);

                $book = new Book;
                $book->title = $request->input('title');
                $book->description = $request->input('description');
                $book->author = $request->input('author');
                $book->image = $pathurl;
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

        Log::info('book created',['admin_id'=>$book->user_id]);
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
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'price' => 'numeric',
            'quantity'=>'integer|min:1'
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }
        
        try 
        {
            $id = $request->input('id');
            $currentUser = JWTAuth::parseToken()->authenticate();
            if ($currentUser)
            {
                $admin = User::select('id')->where([
                    ['role','=','admin'],
                    ['id','=',$currentUser->id]
                ])->get();
        
                if(count($admin)==0)
                {
                    return response()->json(['message' => 'Unauthorised'], 403);
                }
                $book = Book::find($request->id);
                
                if(!$book)
                {
                    return response()->json(['message' => 'Book not Found'], 404);
                }

                if($request->image)
                {
                    $path = str_replace(env('AWS_URL_PATH'),'',$book->image);
            
                    if(Storage::disk('s3')->exists($path)) 
                    {
                        Storage::disk('s3')->delete($path);
                    }
                    $path = Storage::disk('s3')->put('images', $request->image);
                    $pathurl = Storage::disk('s3')->url($path);
                    $book->image = $pathurl;
                }

                $book->fill($request->except('image'));
                if($book->save())
                {
                    return response()->json(['message' => 'Book updated Sucessfully' ], 201);
                }
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
            
            $book=Book::find($request->id);
            if(!$book)
            {
                return response()->json([ 'message' => 'Book not Found'], 404);
            }

            $path = str_replace(env('AWS_URL_PATH'),'',$book->image);
            if(Storage::disk('s3')->exists($path)) 
            {
                Storage::disk('s3')->delete($path);
                if($book->delete())
                {
                    Log::info('book deleted',['user_id'=>$currentUser,'book_id'=>$request->id]);
                    return response()->json(['message' => 'Book deleted Sucessfully'], 201);
                }
            }     
            return response()->json(['message' => 'File image was not deleted'], 402);    
        }
        catch(Exception $e)
        {
            return response()->json(['message' => 'Invalid authorization token' ], 404);
        }
    }

    

    public function getAllBooks()
    {           
        $books = Book::select('id','title','description','author','image','price','quantity')->get();
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
